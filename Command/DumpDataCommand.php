<?php

/**
 * User: Ignacio Velázquez Gómez <igmacio.velazquez@ideup.com>
 * Date: 12/7/11
 * Time: 10:47 AM
 */

namespace Ideup\PachubeBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface,
    Ideup\PachubeBundle\Entity\Pachube,
    Ideup\PachubeBundle\Connection\Connection,
    GNF\SmartMeterBundle\Entity\HomeEnergy;

class DumpDataCommand extends ContainerAwareCommand
{

    const INTERVAL = 15;

    protected function configure()
    {
        $this
                ->setName('pachube:dump:data')
                ->setDescription('Recovers historic data from all the house bridges stored in database')
                ->setDefinition(array(
                    new InputArgument(
                            'start', InputArgument::REQUIRED, 'Start date'
                    ),
                    new InputArgument(
                            'end', InputArgument::REQUIRED, 'End date'
                    ),
                    new InputArgument(
                            'file', InputArgument::REQUIRED, 'File to dump'
                    ),
                    new InputArgument(
                            'bridge', InputArgument::OPTIONAL, 'Bridge', null
                    ),
                    new InputArgument(
                            'timezone', InputArgument::OPTIONAL, 'Timezone', 'Europe/Madrid'
                    ),
                ));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$fp = fopen($input->getArgument('file'), 'w')) {
            throw new Exception('cant open ' . $input->getArgument('file') . ' in w mode ');
        }

        $apiVersion = 'v2';
        $start = $input->getArgument('start');
        $end = $input->getArgument('end');
        $bridgeSerial = $input->getArgument('bridge');
        $timezone = $input->getArgument('timezone');
        $this->data = array('serial', 'consumption', 'power', 'date');

        $this->em = $this->getContainer()->get('doctrine')->getEntityManager('default');
        $this->acquisitionManager = $this->getContainer()->get('gnf.house_energy_acquisition_manager');
        $this->energyManager = $this->getContainer()->get('gnf.home_energy_manager');

        $startDate = new \DateTime($start, new \DateTimeZone($timezone));
        $startDate->setTimezone(new \DateTimeZone('UTC'));
        $endDate = new \DateTime($end, new \DateTimeZone($timezone));
        $endDate->setTimezone(new \DateTimeZone('UTC'));

        if ($bridgeSerial !== null) {
            $bridge = $this->em->getRepository('GNFSmartMeterBundle:HouseBridge')->findOneBySerial($bridgeSerial);
            $this->recoverData($apiVersion, $bridge, $startDate, $endDate, $input, $output);
        } else {
            $houseBridges = $this->em->getRepository('GNFSmartMeterBundle:HouseBridge')->findActivatedBridges();
            foreach ($houseBridges as $bridge) {
                $this->recoverData($apiVersion, $bridge, $startDate, $endDate, $input, $output);
            }
        }


        foreach ($this->data as $item) {
            fputcsv($fp, $item);
        }
        fclose($fp);
    }

    public function recoverData($apiVersion, $bridge, \DateTime $startDate, \DateTime $endDate, $input, $output)
    {
        $output->writeln("\n\n\n+++++++++++++++++++++++++++ <info>{$bridge->getSerial()}</info> +++++++++++++++++++++++++++\n");

        $nextDate = clone $startDate;

        // splits the requests of the API Pachube into pieces of 6 hours of range
        while ($nextDate < $endDate) {

            $startRange = clone $nextDate;
            $nextDate->modify('+4 hours');
            if ($nextDate > $endDate) {
                $nextDate = $endDate;
            }

            $data = $this->getContainer()->get('ideup.pachube.manager')->readFeed(
                    $apiVersion, $bridge->getFeedId(), $bridge->getApiKey(), $startRange->format('Y-m-d H:i:s'), $nextDate->format('Y-m-d H:i:s')
            );

            $data = json_decode($data, true);

            if (isset($data['status'])) {
                $output->writeln("================================== <comment>{$data['status']}</comment> =======================================");
            }

            if (isset($data['errors'])) {
                $output->writeln("<error>{$data['errors']}</error>");
                continue;
            }

            if (!isset($data['datastreams'][1]['datapoints'])) {
                continue;
            }
            $points = $data['datastreams'][1]['datapoints'];

            foreach ($points as $point) {
                $pointDate = new \DateTime($point['at']);

                //Looking for the entry in HOuseBridgAcquisition
                $exist = $this->em
                        ->getRepository('GNFSmartMeterBundle:HouseEnergyAcquisition')
                        ->findOneBy(array(
                    'serial' => $bridge->getSerial(),
                    'realDate' => $pointDate
                        ));

                $intervalDate = $this->dateToInterval($pointDate);

                if ($exist !== null) {
                    $output->writeln("<comment>Aquisition entry already exists:</comment> {$intervalDate->format('Y-m-d H:i:s')}");
                    continue;
                }

                //Creating new temporary data register
                $acquisition = $this->acquisitionManager->create(
                        $bridge->getSerial(), $point['value'], $pointDate, $intervalDate
                );

                $this->acquisitionManager->update($acquisition); //save
                $output->writeln("<info>Aquisition entry created:</info> {$intervalDate->format('Y-m-d H:i:s')} => <comment>{$point['value']}</comment>");

                //Looking for former intervals items of the current serial if exists
                $formerAcquisitions = $this->em->getRepository('GNFSmartMeterBundle:HouseEnergyAcquisition')
                        ->calculateAverageOfFormerIntervals($bridge->getSerial(), $intervalDate);

                if (count($formerAcquisitions) > 0) {
                    foreach ($formerAcquisitions as $item) {
                        $output->writeln("<info>Found former acquisitions</info> <comment>@</comment> {$item['quarter']} => <comment>{$item['average']}</comment>");

                        //Looking for the entry in HOuseBridgAcquisition
                        $homeEnergy = $this->em
                                ->getRepository('GNFSmartMeterBundle:HomeEnergy')
                                ->findOneBy(array(
                            'houseBridge' => $bridge->getId(),
                            'hour' => new \DateTime($item['quarter'])
                                ));

                        $homeEnergy = $this->energyManager->create(
                                $item['average'], new \DateTime($item['quarter']), $bridge
                        );
                        $this->persist($homeEnergy);
                        $output->writeln("======> <info>Energy entry inserted</info> <comment>@</comment> {$item['quarter']} => <comment>{$item['average']}</comment>");
                    }
                    //Removing last items from acquisition
                    $this->acquisitionManager->removeByDate($bridge->getSerial(), $intervalDate);
                }
            }//no more points
        }//no more ranges

        $lastDate = $this->dateToInterval($endDate);

        //Cleaning acquisition
        $this->acquisitionManager->removeByDate($bridge->getSerial(), $lastDate);

        $output->writeln("<info>Cleaning Acquisition registers before</info> {$lastDate->format('Y-m-d H:i:s')}");
    }

    public function persist(HomeEnergy $homeEnergy)
    {
        array_push($this->data, array(
            'home_serial_bridge' => $homeEnergy->getHouseBridge()->getSerial(),
            'consumption' => $homeEnergy->getConsumption(),
            'power' => $homeEnergy->getPower(),
            'datetime' => $homeEnergy->getHour()->format('Y-m-d H:i:s')
                ));
    }

    /**
     * Takes a date and changes it to the minimun value of the interval
     *
     * @param \DateTime $date
     * @return \DateTime
     */
    public function dateToInterval(\DateTime $date)
    {
        $min = $date->format('i');
        $min = ((int) ($min / self::INTERVAL)) * self::INTERVAL;

        $intervalDate = clone $date;
        $intervalDate->setTime($date->format('H'), $min, 0);

        return $intervalDate;
    }

}

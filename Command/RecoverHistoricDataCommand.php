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
Ideup\PachubeBundle\Connection\Connection;

class RecoverHistoricDataCommand extends ContainerAwareCommand
{
    const INTERVAL = 15;

    protected function configure()
    {
        $this
            ->setName('pachube:recover:data')
            ->setDescription('Recovers historic data from all the house bridges stored in database')
            ->setDefinition(array(
            new InputArgument(
                'start', InputArgument::REQUIRED, 'Start date'
            ),
            new InputArgument(
                'end', InputArgument::REQUIRED, 'End date'
            ),
            new InputArgument(
                'timezone', InputArgument::OPTIONAL, 'Timezone', 'Europe/Madrid'
            ),
        ));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apiVersion = 'v2';
        $start = $input->getArgument('start');
        $end = $input->getArgument('end');
        $timezone = $input->getArgument('timezone');

        $em = $this->getContainer()->get('doctrine')->getEntityManager('default');
        $acquisitionManager = $this->getContainer()->get('gnf.house_energy_acquisition_manager');
        $energyManager = $this->getContainer()->get('gnf.home_energy_manager');

        $startDate = new \DateTime($start, new \DateTimeZone($timezone));
        $startDate->setTimezone(new \DateTimeZone('UTC'));
        $endDate = new \DateTime($end, new \DateTimeZone($timezone));
        $endDate->setTimezone(new \DateTimeZone('UTC'));

        $houseBridges = $em->getRepository('GNFSmartMeterBundle:HouseBridge')->findActivatedBridges();

        foreach ($houseBridges as $bridge) {
            $output->writeln("\n\n\n+++++++++++++++++++++++++++ <info>{$bridge->getSerial()}</info> +++++++++++++++++++++++++++\n");

            $nextDate = clone $startDate;

            // splits the requests of the API Pachube into pieces of 6 hours of range
            while($nextDate < $endDate){

                $startRange = clone $nextDate;
                $nextDate->modify('+6 hours');
                if ($nextDate > $endDate){
                    $nextDate = $endDate;
                }

                $data = $this->getContainer()->get('ideup.pachube.manager')->readFeed(
                    $apiVersion,
                    $bridge->getFeedId(),
                    $bridge->getApiKey(),
                    $startRange->format('Y-m-d H:i:s'),$nextDate->format('Y-m-d H:i:s')
                );

                $data = json_decode($data, true);

                $output->writeln("================================== <comment>{$data['status']}</comment> =======================================");

                if (isset($data['errors'])){
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
                    $exist = $em
                        ->getRepository('GNFSmartMeterBundle:HouseEnergyAcquisition')
                        ->findOneBy(array(
                        'serial'   => $bridge->getSerial(),
                        'realDate' => $pointDate
                    ));

                    $intervalDate = $this->dateToInterval($pointDate);

                    if ($exist !== null){
                        $output->writeln("<comment>Aquisition entry already exists:</comment> {$intervalDate->format('Y-m-d H:i:s')}");
                        continue;
                    }

                    //Creating new temporary data register
                    $acquisition = $acquisitionManager->create(
                        $bridge->getSerial(),
                        $point['value'],
                        $pointDate,
                        $intervalDate
                    );

                    $acquisitionManager->update($acquisition); //save
                    $output->writeln("<info>Aquisition entry created:</info> {$intervalDate->format('Y-m-d H:i:s')} => <comment>{$point['value']}</comment>");

                    //Looking for former intervals items of the current serial if exists
                    $formerAcquisitions = $em->getRepository('GNFSmartMeterBundle:HouseEnergyAcquisition')
                        ->calculateAverageOfFormerIntervals($bridge->getSerial(), $intervalDate);


                    if (count($formerAcquisitions) > 0) {
                        foreach ($formerAcquisitions as $item) {
                            $output->writeln("<info>Found former acquisitions</info> <comment>@</comment> {$item['quarter']} => <comment>{$item['average']}</comment>");

                            //Looking for the entry in HOuseBridgAcquisition
                            $exist = $em
                                ->getRepository('GNFSmartMeterBundle:HomeEnergy')
                                ->findOneBy(array(
                                'houseBridge'   => $bridge->getId(),
                                'hour' => new \DateTime($item['quarter'])
                            ));

                            if ($exist == null){
                                $homeEnergy = $energyManager->create(
                                    $item['average'],
                                    new \DateTime($item['quarter']),
                                    $bridge
                                );

                                $energyManager->update($homeEnergy); //save
                                $output->writeln("======> <info>Energy entry inserted</info> <comment>@</comment> {$item['quarter']} => <comment>{$item['average']}</comment>");
                            }
                            else{
                                $output->writeln("<comment>Entry already exists on HomeEnergy</comment> {$item['quarter']} => {$item['average']}");
                            }

                        }
                        //Removing last items from acquisition
                        $acquisitionManager->removeByDate($bridge->getSerial(), $intervalDate);
                    }
                }//no more points

            }//no more ranges

            $lastDate = $this->dateToInterval($endDate);

            //Cleaning acquisition
            $acquisitionManager->removeByDate($bridge->getSerial(), $lastDate);

            $output->writeln("<info>Cleaning Acquisition registers before</info> {$lastDate->format('Y-m-d H:i:s')}");
        }//no more bridges
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
        $min = ((int)($min/self::INTERVAL))*self::INTERVAL;

        $intervalDate = clone $date;
        $intervalDate->setTime($date->format('H'), $min, 0);

        return $intervalDate;
    }
}

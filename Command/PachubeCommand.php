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

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\Query\ResultSetMapping;

class PachubeCommand extends ContainerAwareCommand
{
    /**
     * @var int interval
     */
    protected $interval = 15;

    protected function configure()
    {
        $this
            ->setName('pachube:feed:read')
            ->setDescription('Reads the given feed')
            ->setDefinition(array(
                new InputArgument(
                    'apiVersion', InputArgument::REQUIRED, 'Pachube API version (1 or 2).'
                ),
                new InputArgument(
                    'feedId', InputArgument::REQUIRED, 'Feed id.'
                ),
                new InputArgument(
                    'apiKey', InputArgument::REQUIRED, 'API Key.'
                ),
                new InputArgument(
                    'start', InputArgument::OPTIONAL, 'Start date'
                ),
                new InputArgument(
                    'end', InputArgument::OPTIONAL, 'End date'
                ),
            ))
            ->addOption('dump', null, InputOption::VALUE_NONE, 'Dumps to standard output the complete message structure')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apiVersion = $input->getArgument('apiVersion');
        $apiKey = $input->getArgument('apiKey');
        $feedId = $input->getArgument('feedId');
        $start = $input->getArgument('start');
        $end = $input->getArgument('end');

        $startDate = new \DateTime($start);
        $endDate = new \DateTime($end);

        $em = $this->getContainer()->get('doctrine')->getEntityManager();

        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('GNFSmartMeterBundle:HouseBridge', 'e');

        $query = "
            SELECT e.*
            FROM etc_pm_house_bridge e
            WHERE
                e.house_id IS NOT NULL
        ";

        //$results = $this->getContainer()->get('doctrine')->getEntityManager('default')->createNativeQuery($query, $rsm)->getArrayResult();
        $results = $this->getContainer()->get('doctrine')->getEntityManager('default')->getRepository('GNFSmartMeterBundle:HouseBridge')->findAll();

        foreach ($results as $r) {
          if ($r->getHouseId() === null) {
            continue;
          }

          $data = $this->getContainer()->get('ideup.pachube.manager')->readFeed($apiVersion, $r->getFeedId(), $r->getApiKey(), $start, $end);

            ob_start();
            var_dump($data);
            $buff = ob_get_clean();
            $filename = $r->getId() . '--' . $startDate->format('Y-m-d H:i:s');
            file_put_contents("/tmp/$filename.json", $buff);

            $data = json_decode($data, true);

          if (!isset($data['datastreams'][1]['datapoints'])) {
            continue;
          }
            $points = $data['datastreams'][1]['datapoints'];
            foreach ($points as $point) {
              $interval = $this->dateToInterval(new \DateTime($point['at']));

              $homeEnergy = $this->getContainer()->get('doctrine')->getRepository('GNFSmartMeterBundle:HomeEnergy')->findOneBy(array(
                'hour' => $interval,
                'houseBridge' => $r->getId()
              ));

              if (null != $homeEnergy) {
                continue;
              }

              $homeEnergy = $this->getContainer()->get('gnf.home_energy_manager')->create(
                $point['value'],
                $interval,
                $r
              );

              $homeEnergy = $this->getContainer()->get('gnf.home_energy_manager')->update($homeEnergy);
            }
        }
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
        $min = ((int)($min/$this->interval))*$this->interval;

        $intervalDate = clone $date;
        $intervalDate->setTime($date->format('H'), $min, 0);

        return $intervalDate;
    }


    /**
     * Checks timestamp interval among requests
     *
     * @param \DateTime $date
     * @return bool
     */
    protected function isValidTimestampInterval(\DateTime $date)
    {
        $timestamp = $date->getTimestamp();
        $now = new \DateTime('now');
        $now_timestamp = $now->getTimestamp();
//        var_dump($date->format('Y-m-d H:i:s'));
//        var_dump($now->format('Y-m-d H:i:s'));
        return !(($now_timestamp - $timestamp) > 300);
    }
}

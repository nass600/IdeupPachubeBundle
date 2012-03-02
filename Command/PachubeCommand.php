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

class PachubeCommand extends ContainerAwareCommand
{
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

        $data = $this->getContainer()->get('ideup.pachube.manager')->readFeed($apiVersion, $feedId, $apiKey, $start, $end);

        //time-dependant data
        if ($start != null && $end != null){
//            var_dump($data);
        }
        //no time-dependant data
        else{
            // Error throwing
            if (!empty($data->errors)){
                foreach ($data->errors as $error)
                    $output->writeln('<error>'.$error.'</error>');
            }
            // Receiving
            else{
                $date = new \DateTime($data->at);
    //            var_dump($this->isValidTimestampInterval($date));die;
                if ($input->getOption('dump')) {
                    $output->writeln(var_dump($data));
                }
                else
                    $output->writeln('<info>'.$date->format('Y-m-d H:i:s') .'</info> > <comment>'. $data->current_value . ' W</comment>');
            }
        }
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

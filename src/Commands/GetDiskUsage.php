<?php 

namespace App\Commands;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class GetDiskUsage extends Command {

    /**
     * Define the command signature.
     *
     * @var  string 
     */
    protected static $defaultName = 'app:get:disk-usage';

    /**
     * The Command Description.
     *
     * @var  string
     */
    protected $description = 'Get the disk usage.';

    /**
     * The QUEUE NAME
     *
     * @var  string
     */
    protected const QUEUE_NAME = 'system_report'; 
    /**
     * Configure the Command.
     *
     * @return void
     */
    protected function configure() {
        $this
            ->setDescription($this->description);
    }

    /**
     * Execute the command.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output) {

        $connection = new AMQPStreamConnection(
            getenv("RABBITMQ_HOST"),
            getenv("RABBITMQ_PORT"),
            getenv("RABBITMQ_USERNAME"),
            getenv("RABBITMQ_PASSWORD")
        );

        $channel = $connection->channel();
        $channel->queue_declare(self::QUEUE_NAME, false, false, false, false);


        $output->writeln("<info>Waiting for Message Broker. To exit press CTRL+C</info>");

        $channel->basic_consume(self::QUEUE_NAME, "", false, true, false, false, function($message) use($output) {

            $output->write(sprintf("\033\143"));

            $data = json_decode($message->body);

            if(is_null($data) || $data->type != 'disk') {

                $output->writeln("<error>Unable to parse the disk data!</error>");

                // non-zero exit code means command waw unsuccessful
                return 1;
            }

            $rows = [];


            foreach($data->disks as $disk) {
                $rows[] = [
                    $disk->mount_point,
                    $disk->fs_type,
                    to_size($disk->total),
                    to_size($disk->used),
                    to_size($disk->free),
                    sprintf("%s%%", round($disk->used_percent))
                ];
            }

            $output->writeln("<info>Below is the Disk Usages Table. To exit press CTRL+C</info>");

            $table = new Table($output);

            $table
                ->setHeaders(['Mount Point', 'FS Type', 'Total', 'Used', 'Free', 'Used Percent'])
                ->setRows($rows);

            $table->render();

            $output->writeln("<comment>Table refreshed every 10 secs.</comment>");

        });

        while($channel->is_consuming()) {
            $channel->wait();
        }

        // 0 exit code indicates the command executed successfully
        return 0;

    }
}

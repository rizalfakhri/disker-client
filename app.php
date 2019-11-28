<?php 

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Commands\GetDiskUsage;
use Symfony\Component\Console\Application;

/**
 * Configure the DOTENV
 */
$dotenv = Dotenv::create(__DIR__);
$dotenv->load();

$app = new Application();

// Register the commands
$app->add(new GetdiskUsage());


// Run the APP
$app->run();

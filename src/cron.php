<?php

use Dotenv\Dotenv;
use Pingdom\Client;
use Damianopetrungaro\CachetSDK\CachetClient;
use Damianopetrungaro\CachetSDK\Components\ComponentFactory;
use Damianopetrungaro\CachetSDK\Points\PointFactory;

// Check if composer dependencies are installed
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo 'Error: Run `composer install` before running this script.' . PHP_EOL;
    die;
}

// Require the composer autoloader file
require __DIR__ . '/../vendor/autoload.php';

// Load the configuration file
$dotenv = new Dotenv(__DIR__ . '/../');
$dotenv->load();
$dotenv->required([
    'PINGDOM_USERNAME',
    'PINGDOM_PASSWORD',
    'PINGDOM_API_KEY',
    'CACHET_HOST',
    'CACHET_API_KEY',
    'COMPONENTS_MAP',
    'METRICS_MAP',
])->notEmpty();

// Little helper to parse the maps
$extractMap = function ($map) {
    list($cachet, $pingdom) = explode(':', $map);

    return compact('cachet', 'pingdom');
};

function write($line)
{
    echo $line.PHP_EOL;
}

// Parse the metrics & components map
$metricsMap    = array_map($extractMap, explode(',', getenv('METRICS_MAP')));
$componentsMap = array_map($extractMap, explode(',', getenv('COMPONENTS_MAP')));

// Initialize the Cachet client library
$cachetClient = new CachetClient(getenv('CACHET_HOST') . '/api/v1/', getenv('CACHET_API_KEY'));
$componentManager = ComponentFactory::build($cachetClient);
$cachetPoints = PointFactory::build($cachetClient);

// Initialize the Pingdom client library
$pingdomClient = new Client(getenv('PINGDOM_USERNAME'), getenv('PINGDOM_PASSWORD'), getenv('PINGDOM_API_KEY'));

$checks = $pingdomClient->getChecks();
foreach ($checks as $check) {
    foreach ($componentsMap as $componentMap) {
        if ($componentMap['pingdom'] == $check['id']) {
            write("[Component] Updating Pingdom {$componentMap['pingdom']} to Cachet {$check['id']} with status: {$check['status']}");

            $component = $componentManager->updateComponent($componentMap['cachet'], [
                'status' => (int) ($check['status'] == 'up' ?: 4),
            ]);
        }
    }
}

// Update the metrics
foreach ($metricsMap as $metricMap) {
    $result = $pingdomClient->getResults($metricMap['pingdom'], 1)[0];
    $point  = ['value' => $result['responsetime'], 'timestamp' => $result['time']];

    write("[Metric] Create point for Pingdom check:{$metricMap['pingdom']} to Cachet metric:{$metricMap['cachet']}");
    write('[Metric] Point data: ' . json_encode($point));

    $cachetPoints->storePoint($metricMap['cachet'], $point);
}

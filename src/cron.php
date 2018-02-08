<?php

use Dotenv\Dotenv;
use Pingdom\Client;
use Damianopetrungaro\CachetSDK\CachetClient;
use Damianopetrungaro\CachetSDK\Points\PointFactory;
use Damianopetrungaro\CachetSDK\Components\ComponentFactory;

// Require our helpers
require __DIR__ . '/helpers.php';

// Check if composer dependencies are installed
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    write('Error: Run `composer install` before running this script.');
    exit(1);
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
])->notEmpty();


// Initialize the API clients
$cachetClient  = new CachetClient(getenv('CACHET_HOST') . '/api/v1/', getenv('CACHET_API_KEY'));
$pingdomClient = new Client(getenv('PINGDOM_USERNAME'), getenv('PINGDOM_PASSWORD'), getenv('PINGDOM_API_KEY'));


// Parse the metrics mapping
$metricsMap = array_filter(array_map('extractMap', explode(',', env('METRICS_MAP', ''))), function ($map) {
    return !empty($map);
});

// Run the metrics section only if there are metrics mapped
if (!empty($metricsMap)) {
    // Load up the cachet point client to write data points to
    $cachetPoints = PointFactory::build($cachetClient);

    // Run over all the mapped metrics to write new points retrieved from Pingdom
    foreach ($metricsMap as $metricMap) {
        $results = $pingdomClient->getResults($metricMap['pingdom'], (int)env('PINGDOM_RESULT_COUNT', 2));

        foreach ($results as $result) {
            // There is only a response time available when the status is up
            if ($result['status'] === 'up') {
                $point = ['value' => $result['responsetime'], 'timestamp' => $result['time']];

                write("[Metric] Write point from Pingdom check:{$metricMap['pingdom']} to Cachet metric:{$metricMap['cachet']} (" . json_encode($point) . ')');

                $cachetPoints->storePoint($metricMap['cachet'], $point);
            } else {
                write("[Metric] No point for Pingdom check:{$metricMap['pingdom']} to Cachet metric:{$metricMap['cachet']} because status:{$result['status']}");
            }
        }
    }
} else {
    write('[Metric] Section skipped since no (valid) mapping was found.');
}


// Parse the components mapping
$componentsMap = array_filter(array_map('extractMap', explode(',', env('COMPONENTS_MAP', ''))), function ($map) {
    return !empty($map);
});

// Run the components section only if there are components mapped
if (!empty($componentsMap)) {
    // Get the checks from pingdom to map the component statuses
    $pingdomChecks = $pingdomClient->getChecks();

    // Load up the cachet component client to write component updates to
    $cachetComponents = ComponentFactory::build($cachetClient);

    // Run over all the checks and execute updates if needed
    foreach ($pingdomChecks as $check) {
        foreach ($componentsMap as $componentMap) {
            if ($componentMap['pingdom'] == $check['id']) {
                $data = $cachetComponents->getComponent($componentMap['cachet']);
                $data = $data['data'];
                $newStatus = $check['status'] == 'down' ? 4 : 1;

                if ($data['status'] == $newStatus) {
                    write("[Component] Skipping Pingdom check:{$componentMap['pingdom']}, because the status in Cachet is already equal.");
                    continue;
                }
                
                write("[Component] Updating Pingdom check:{$componentMap['pingdom']} to Cachet component:{$check['id']} with status:{$check['status']}");

                $component = $cachetComponents->updateComponent($componentMap['cachet'], [
                    'status' => ($check['status'] == 'down' ? 4 : 1),
                ]);
            }
        }
    }
} else {
    write('[Component] Section skipped since no (valid) mapping was found.');
}

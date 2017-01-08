# Pingdom to Cachet CRON

A very simple cron script to send [Pingdom](https://www.pingdom.com/) data to [Cachet](https://cachethq.io/).


## Prerequesites

* Git
* Composer
* PHP 7.0 or newer

## Usage

Clone the repository to your server

```bash
git clone https://github.com/stayallive/pingdom-to-cachet
cd pingdom-to-cachet
```

Install composer dependencies

```bash
composer install --no-dev -o
```

Copy the example `.env` file to the real one

```bash
cp .env.example .env
```

Edit `.env` with your favorite editor (ex. `nano .env`) and update the values:

* `PINGDOM_USERNAME`: Your Pingdom username
* `PINGDOM_PASSWORD`: Your Pingdom password
* `PINGDOM_API_KEY`: Your Pingdom API key / token
* `CACHET_HOST`: The location of your Cachet installation (ex. `https://status.example.com`)
* `CACHET_API_KEY`: The API key of your Cachet user
* `COMPONENTS_MAP`: The mapping between you Cachet component ID and your Pingdom check ID (ex. `1:12345,2:54321`)
* `METRICS_MAP`: The mapping between you Cachet matric ID and your Pingdom check ID (ex. `1:12345,2:54321`)

The maps need to be defined in the following format: `<CACHET_ID>:<PINGDOM_ID>`.
If you want more than 1 mapping you can seperate them with a `,` (without spaces)

If everything is setup you can manually check if everything is running smoothly by executing

```bash
php src/cron.php
```

You should see output about the data that is retrieved from Pingdom and sent to Cachet.

You can add a CRON entry to run the cron overy minute (or other interval) to update the components and metrics.

```
# m   h  dom mon dow   command
# Run the Pingdom to Cachet CRON every minute
  *   *  *   *   *     php /path/to/pingdom-to-cachet/src/cron.php > /dev/null 2>&1
# Run the Pingdom to Cachet CRON every 5 minutes
# */5 *  *   *   *     php /path/to/pingdom-to-cachet/src/cron.php > /dev/null 2>&1
```


## Upgrading

Simply run `git pull` where you pulled the repository to, easy as cake.


## Security Vulnerabilities

If you discover a security vulnerability, please send an e-mail to Alex Bouma at me@alexbouma.me. All security vulnerabilities will be swiftly addressed.


## License

The Pingdom to Cachet CRON is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).

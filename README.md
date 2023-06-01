# ECDeploy

Deploy your project onto multiple servers with ease.

![GitHub issues](https://img.shields.io/github/issues/elijahcruz12/ecdeploy) ![Packagist Downloads](https://img.shields.io/packagist/dm/elijahcruz/ecdeployer) ![Packagist PHP Version](https://img.shields.io/packagist/dependency-v/elijahcruz/ecdeployer/php)

## Installation

To install, all it takes is a simple composer require

```bash
composer global require elijahcruz/ecdeployer
```

## Usage

## Commands

### Init

You can use the init command to create a new deploy.json file in your project.

```bash
ecdeploy init
```

Note that if a deploy.json file already exists, it will as if you want it to be overwritten.

If you add `--laravel` it will create a deploy.json file with the default laravel configuration.

If you add `--gitignore` it will add the deploy.json file to your .gitignore file.

### Deploy

You can use the deploy command to deploy your project to your servers.

```bash
ecdeploy deploy
```

Using `--all` will deploy to all servers.

Using `--tags` will deploy to all servers with the specified tags.

If you don't select any options, it will prompt you to select which servers you want to deploy to. You can select multiple servers by separating them with a comma.

## Configuration

### deploy.json

The deploy.json file is where you configure your servers and your project.

```json

{
    "name": "myproject",
    "repo": "git@github.com:myproject/myproject.git",
    "servers": [
        {
            "name": "prod-server",
            "host": "192.168.1.1",
            "user": "root",
            "port": 22,
            "tags": [
                "prod"
            ],
            "path": "~/myproject",
            "commands": [
                "before",
                "during",
                "after"
                "node",
            ]
        }
    ],
    "commands": {
        "before": [
            "php artisan down",
            "php artisan optimize:clear",
            "git pull origin master",
            "composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader"
        ],
        "queue-pause": [
            "php artisan horizon:pause"
        ],
        "during": [
            "php artisan migrate --force",
            "php artisan config:cache",
            "php artisan route:cache",
            "php artisan view:cache"
        ],
        "node": [
            "npm install",
            "npm run build"
        ],
        "after": [
            "php artisan up"
        ],
        "queue-resume": [
            "php artisan horizon:continue"
        ]
    }
}


```

### Servers

All servers are defined in the servers array. Each server has the following properties:
- Name: The name of the server
- Host: The IP address or domain of the server
- User: The user to connect to the server with
- Port: The port to connect to the server with
- Tags: An array of tags to identify the server with.
- Path: The path to the project on the server
- Commands: An array of the command stages to run on the server

### Commands

All commands are defined in stages. The stages are run in the order they are defined in the commands array. So in the example above, `node` will run before `after`, even though `after` is defined before `node` in the server's command array.

The stages names can be anything you want, and you can have as many stages as you want. The only exception is the `node` stage.

You also don't need to create the path on the server, it will be created automatically if it doesn't exist.


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

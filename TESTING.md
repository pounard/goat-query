# Running tests

Having a complete test matrix that you can run locally is not an easy task.
For solving this problem, a simple `docker-compose.yaml` file along with
a few scripts to help you run test:

 - `docker-compose` file spawns multiple SQL backends each one in a container.
 - It also spawn as many PHP containers as we wish to test.
 - `phpunit` tests will test as many backends as specified in environment
   variables - read `phpunit.xml` file for allowed list.
 - `sys/docker-run.sh` will run `phpunit` with a few options for each PHP
   container.

## Create docker containers

Simply run this command:

```sh
cd sys
./docker-up.sh
```

You may also use `docker-compose down` to stop them all.

## Run tests

Simply run this command:

```sh
cd sys
./docker-run.sh
```

You may also pass `phpunit` options in the command line, for example:

```sh
cd sys
./docker-run.sh --stop-on-error --stop-on-failure -vvv
```

Or change environment variables as well:

```sh
cd sys
XDEBUG_CONFIG="mode=debug" ./docker-run.sh
```

For now XDebug is not enabled yet in PHP containers `Dockerfile`, but it will
be really soon - it needs you to be able to configure the remote host and port
at the bare minimum to make it work, this is unsolved yet.

## Notes

 - PHP 8 are hard-disabled due to bug we still did not fixed,
 - you probably need to know docker very well for doing advanced debugging.

## Destroy and recreate containers

```sh
cd sys
./docker-rebuild.sh
```

Warning: this does not delete container's data, if you need to, you need to use
`docker ps` to find containers, then use `docker kill` and `docker rm` to kill
and delete all container's data.

## Todolist

 - Allow env variables to be arrays, in order to be able to add new drivers
   without patching test classes.
 - Find the PHP 8 bug that creates a confusion between `pdo` and `ext-pgsql`
   drivers.

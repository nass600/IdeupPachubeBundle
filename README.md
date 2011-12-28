# IdeupPachubeBundle

The IdeupPachubeBundle offers access to the pachube.com feed API either by using a PHP service or through the CLI
for Symfony2. Is on charge of handle all the operations related to pachube feeds via the bundle CRUD interface.

Features include:
- CRUD API for managing feeds
- CLI commands for executing operations


What is Pachube
===============

Pachube is an web service provider allowing developers to connect their own data (energy and environment data
from objects, devices & buildings) to the Web and to build their own applications on it.

For more information about the service, please visit: https://pachube.com

[![Build Status](https://secure.travis-ci.org/nass600/IdeupPachubeBundle.png?branch=master)](http://travis-ci.org/nass600/IdeupPachubeBundle)

Installation
============

Add PachubeBundle to your vendor/bundles/ directory.

Add the following lines in your ``deps`` file::

    [IdeupPachubeBundle]
      git =https://github.com/nass600/IdeupPachubeBundle.git
      target=/bundles/Ideup/PachubeBundle
      version=master

Run the vendors script::

    ./bin/vendors install

Add the Ideup namespace to your `app/autoload.php`::

    // app/autoload.php
    $loader->registerNamespaces(array(
        // your other namespaces
        'Ideup' => __DIR__.'/../vendor/bundles',
    );


Add PachubeBundle to your `app/AppKernel.php`::

    // app/AppKernel.php

    public function registerBundles()
    {
        return array(
            // ...
            new Ideup\PachubeBundle\IdeupPachubeBundle(),
        );
    }


Usage
=====

Create
------

Read
----

As a service::

    public function indexAction(Request $request){
      $version = 'v2';
      $feedId = 34278;
      $apiKey = 'hjg34tg73u34grutd78tr34g3rt478qiwhe8923';

      $pachube = $this->getContainer()->get('ideup.pachube.manager');

      $data = $pachube->readFeed($version, $feedId, $apiKey);
    }


As a command::

    $ php ./app/console ideup:feed:read version feedId apiKey

Update
------

Delete
------


License
=======

This bundle is under de GNU license. See the complete license in::

    LICENSE

Authors
=======

- Ignacio Velázquez Gómez

Credits
=======

The bundle structure is partially based on [pachube_php](https://github.com/pachube/pachube_php)

TODO
====

- Unit testing
- Add config options

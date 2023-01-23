<?php
/* bzflag-listkeymgr
 * Copyright (c) 1993-2023 Tim Riker
 *
 * This package is free software;  you can redistribute it and/or
 * modify it under the terms of the LGPL 2.1 license found in the file
 * named COPYING.txt that should have accompanied this file.
 *
 * THIS PACKAGE IS PROVIDED ``AS IS'' AND WITHOUT ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, WITHOUT LIMITATION, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE.
 */

require(__DIR__.'/vendor/autoload.php');
require("db.php");
require("checkToken.php");

$config = require("config.php");

(new class($config)
{
	private $db;
	private $twig;
	private $config;

	public function __construct($config)
	{
	    $this->config = $config;
		session_start();
		$loader = new \Twig\Loader\FilesystemLoader(__DIR__.'/views/');
		$this->twig = new \Twig\Environment($loader);
        require('/etc/bzflag/serversettings.php');
		$this->db = new DB($dbhost, $dbname, $dbuname, $dbpass);
	}

	private function error($message)
    {
        echo $this->twig->render('error.twig', [
            'error' => $message
        ]);
    }

    private function redirect($url)
    {
        header("Location: $url");
    }

	public function run()
    {
        // Allow the login action to proceed without any other requirements
        if (isset($_GET['action']) && $_GET['action'] == 'login') {
            $this->login();
        }
        // If no user is logged in, redirect to weblogin
        else if (!isset($_SESSION['bzid']) || $_SESSION['bzid'] == -1 ) {
            $this->redirect("https://my.bzflag.org/weblogin.php?url=" . urlencode($this->config['protocol']."://" . $this->config['hostname'] . $this->config['baseURI'] . "?action=login&token=%TOKEN%&user=%USERNAME%"));
        }
        else {
            if (isset($_POST['action'])) {
                if ($_POST['action'] == 'createkey')
                    $this->createKey();
                else if ($_POST['action'] == 'deletekey')
                    $this->deleteKey();
                else
                    $this->error("Invalid POST action");
            } else if (isset($_GET['action'])) {
                if ($_GET['action'] == 'logout')
                    $this->logout();
                else
                    $this->error("Invalid GET action");
            } else {
                $this->listKeys();
            }
        }
    }

    private function login()
    {
        // A login can only be valid when both a token and username are provided
        if (!isset($_GET['token']) || !isset($_GET['user']) ) {
            $this->error("Invalid Entry");
            return;
        }

        // Check if the token is valid
        $checkResults = validate_token($_GET['token'], $_GET['user'], array(), $this->config['checkip']);
        if (!isset($checkResults['bzid'])) {
            $this->error("Invalid Login");
            return;
        }
        $_SESSION['bzid'] = $checkResults['bzid'];
        $this->redirect($this->config['baseURI']);
    }

    private function logout()
    {
        session_destroy();
        $this->redirect('https://www.bzflag.org/');
    }

    private function createKey()
    {
        if (strlen($_POST['hostname']) == 0) {
            $_SESSION['flash'] = 'A hostname must be provided when creating a key.';
            $this->redirect($this->config['baseURI']);
            return;
        }

        // First, let's create a new unique key
        $i = 0;
        do {
            $key = bin2hex(random_bytes(20));
            $i++;

            // If we've tried ~20 times to create a key, give up
            if ($i > 20) {
                $_SESSION['flash'] = 'There was an error generating a key.';
                $this->redirect($this->config['baseURI']);
                return;
            }
        } while ($this->db->getKeyByKey($key) !== false);

        // Create the new DB record
        $this->db->createKey($key, $_POST['hostname'], $_SESSION['bzid']);
        $_SESSION['flash'] = 'The new key has been created.';
        $this->redirect($this->config['baseURI']);
    }

    private function deleteKey()
    {
        if (strlen($_POST['id']) == 0) {
            $this->redirect($this->config['baseURI']);
            return;
        }

        if ($this->db->deleteKey($_POST['id'], $_SESSION['bzid'])) {
            $_SESSION['flash'] = 'The key has been deleted.';
        } else {
            $_SESSION['flash'] = 'There was an error deleting the key.';
        }

        $this->redirect($this->config['baseURI']);
    }

    private function listKeys()
    {
        $keys = $this->db->getKeysByBZID($_SESSION['bzid']);
        echo $this->twig->render('listkeys.twig', [
            'keys' => $keys,
            'flash' => $_SESSION['flash'] ?? '',
            'user' => true
        ]);

        // Only show the message once
        unset($_SESSION['flash']);
    }
})->run();

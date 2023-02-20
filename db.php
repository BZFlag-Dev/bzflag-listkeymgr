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

class DB
{
  var $link;

  function __construct($hostname, $database, $username, $password)
  {
    $this->link = new mysqli($hostname, $username, $password, $database);
    if ($this->link->connect_error) {
      die('Unable to connect to database');
    }

    $this->link->query("SET NAMES 'utf8'");
  }

  function getAffectedRows() { return $this->link->affected_rows; }

  private function getAllAssoc($result) {
    $rows = Array();
    while ($row = $result->fetch_assoc()) {
      $rows[] = $row;
    }
    return $rows;
  }

  function createKey($key, $hostname, $bzid)
  {
    $statement = $this->link->prepare("INSERT INTO authkeys (key_string, owner, host, edit_date) VALUES (?, ?, ?, NOW())");
    if ($statement) {
      $statement->bind_param('sss', $key, $bzid, $hostname);
      $statement->execute();
    }
  }

  function getKeyByKey($key)
  {
    $statement = $this->link->prepare("SELECT host, owner FROM authkeys WHERE key_string = ?");
    if ($statement) {
      $statement->bind_param('s', $key);
      $statement->execute();
      $result = $statement->get_result();
      if ($result) {
        $row = $result->fetch_assoc();
        $statement->free_result();
        if ($row) return $row;
      }
    }
    return false;
  }

  function getHostByHost($host)
  {
    $statement = $this->link->prepare("SELECT host, owner FROM authkeys WHERE host = ?");
    if ($statement) {
      $statement->bind_param('s', $host);
      $statement->execute();
      $result = $statement->get_result();
      if ($result) {
        $row = $result->fetch_assoc();
        $statement->free_result();
        if ($row) return $row;
      }
    }
    return false;
  }

  function getKeysByBZID($bzid)
  {
    $statement = $this->link->prepare("SELECT id, key_string, host FROM authkeys WHERE owner = ?");
    if ($statement) {
      $statement->bind_param('s', $bzid);
      $statement->execute();
      $result = $statement->get_result();
      if ($result) {
        return $this->getAllAssoc($result);
      }
    }
  }

  function deleteKey($keyid, $bzid)
  {
    $statement = $this->link->prepare("DELETE FROM authkeys WHERE ID = ? AND owner = ? LIMIT 1");
    if ($statement) {
      $statement->bind_param('is', $keyid, $bzid);
      if ($statement->execute()) {
          $statement->store_result();
          return ($this->link->affected_rows === 1);
      }
    }
    return false;
  }
}


<?php

namespace Droid\Plugin\Fs\Service;

class PosixAclObjectLookup implements AclObjectLookupInterface
{
    public function userId($name)
    {
        $info = posix_getpwnam($name);
        if ($info === false || !isset($info['uid']) || !is_numeric($info['uid'])) {
            return null;
        }
        return (int) $info['uid'];
    }

    public function userName($id)
    {
        $info = posix_getpwuid($id);
        if ($info === false || !isset($info['name']) || !is_numeric($info['name'])) {
            return null;
        }
        return (string) $info['name'];
    }

    public function groupId($name)
    {
        $info = posix_getgrnam($name);
        if ($info === false || !isset($info['gid']) || !is_numeric($info['gid'])) {
            return null;
        }
        return (int) $info['gid'];
    }

    public function groupName($id)
    {
        $info = posix_getgrid($id);
        if ($info === false || !isset($info['name']) || !is_numeric($info['name'])) {
            return null;
        }
        return (string) $info['name'];
    }
}

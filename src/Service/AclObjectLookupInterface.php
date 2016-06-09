<?php

namespace Droid\Plugin\Fs\Service;

interface AclObjectLookupInterface
{
    /**
     * Lookup user id by user name.
     *
     * @param string $name
     *
     * @return null|integer
     */
    public function userId($name);

    /**
     * Lookup user name by user id.
     *
     * @param int $id
     *
     * @return null|string
     */
    public function userName($id);

    /**
     * Lookup group id by group name.
     *
     * @param string $name
     *
     * @return null|integer
     */
    public function groupId($name);

    /**
     * Lookup group name by group id.
     *
     * @param int $id
     *
     * @return null|string
     */
    public function groupName($id);
}

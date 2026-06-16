<?php

namespace App\Models\Employees;

class WorkerType
{
    private $type;

    const TYPE_WORKER       = 'WRK';
    const TYPE_GROUP_LEADER = 'GRL';
    const TYPE_ADM_STAFF    = 'ADM';
    const TYPE_CLEANING     = 'CLN';
    const TYPE_MANAGER      = 'MGR';
    const TYPE_S_ADMIN      = 'S-ADM';

    const AVAILABLE_TYPES = [
        self::TYPE_WORKER       => 'Worker',
        self::TYPE_GROUP_LEADER => 'Group leader',
        self::TYPE_ADM_STAFF    => 'Administration staff',
        self::TYPE_CLEANING     => 'Cleaning / Maintenance',
        self::TYPE_MANAGER      => 'Manager',
        self::TYPE_S_ADMIN      => 'Super admin',
    ];

    /** Array of types that can have or be in the attendance of the company */
    private $typeForAttendance = [self::TYPE_WORKER, self::TYPE_GROUP_LEADER];

    /** Array of types that should be excluded from other */
    private $excludedTypes = [self::TYPE_S_ADMIN];

    private function __construct($type)
    {
        if (key_exists($type, self::AVAILABLE_TYPES)) $this->type = $type;
    }

    /**
     * Create a new empty instance
     * 
     * @return self
     */
    public static function init()
    {
        return new self(NULL);
    }

    /**
     * Set a new instance from a worker type
     * 
     * @param string $type Passthru the worker type
     * @return self
     */
    public static function setByType(string $type)
    {
        return new self($type);
    }

    /**
     * Set a new instance for worker type
     * 
     * @return self
     */
    public static function setTypeWorker()
    {
        return new self(self::TYPE_WORKER);
    }

    /**
     * Set a new instance for groupe leader type
     * 
     * @return self
     */
    public static function setTypeGroupLeader()
    {
        return new self(self::TYPE_GROUP_LEADER);
    }

    /**
     * Set a new instance for administration type
     * 
     * @return self
     */
    public static function setTypeAdministration()
    {
        return new self(self::TYPE_ADM_STAFF);
    }

    /**
     * Set a new instance for cleaning/maintenance crew type
     * 
     * @return self
     */
    public static function setTypeCleaning()
    {
        return new self(self::TYPE_CLEANING);
    }

    /**
     * Set a new instance for manager type
     * 
     * @return self
     */
    public static function setTypeManager()
    {
        return new self(self::TYPE_MANAGER);
    }

    /**
     * Set a new instance for super admin type
     * 
     * @return self
     */
    public static function setTypeSuperAdmin()
    {
        return new self(self::TYPE_S_ADMIN);
    }

    /**
     * Get the type description
     * 
     * @return string
     */
    public function description()
    {
        return self::AVAILABLE_TYPES[$this->type];
    }

    /**
     * Get the all the employee type
     * 
     * @return array
     */
    public function getAllType($exclude = TRUE)
    {
        $output = self::AVAILABLE_TYPES;
        if ($exclude) {
            foreach ($this->excludedTypes as $type) {
                unset($output[$type]);
            }
        }
        return array_keys($output);
    }

    /**
     * Get types that can be in the attendance
     * 
     * @return array
     */
    public function getTypesForAttendance()
    {
        return $this->typeForAttendance;
    }

    /**
     * Check if the given type can be in attendance
     * 
     * @return boolean
     */
    public function canThisTypeBeInAttendance()
    {
        return in_array($this->type, $this->typeForAttendance);
    }
}

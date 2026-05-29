<?php

namespace App\Services\Attendance;

use App\Services\BaseClassAndStyleObject;

class AbsenceBtnClassAndStyleObject extends BaseClassAndStyleObject
{

    /**All the classes that will be used in a btn instance */
    protected array $class = ['btn', 'no-border-radius', 'shadow'];

    /**All the styles that will be used in a btn instance */
    protected array $style = ['width' => '51px'];

    /**Default background colors */
    protected $defaultBackgroundColor = [
        10 => '#FF7E29',
        20 => '#2998FF',
        30 => '#7E84F7',
    ];

    /**
     * Set the btn size with the options: sm / lg.
     * This will regulate the width of the btn ass well
     * 
     * @return string
     */
    public function setBtnSize(?string $size)
    {
        if ($size === NULL) return $this;
        switch ($size) {
            case 'sm':
                $this->class[] = 'btn-sm';
                $this->style['width'] = '41px';
                break;

            case 'lg':
                $this->class[] = 'btn-lg';
                $this->style['width'] = '61px';
                break;
        }
        return $this;
    }
}

<?php

namespace App\View\Components\Ui;

/**
 * Class LockBtn.
 * A Btn::class with two states, locked/unlocked, each shown with its own icon.
 * The icon reflects the current state: a closed padlock when locked, an open one when not.
 */
class LockBtn extends Btn
{
    const ICONS = [
        'locked'   => 'lock-fill',
        'unlocked' => 'unlock-fill',
    ];

    public bool $locked;

    /**
     * Create a new component instance.
     */
    public function __construct(
        bool $locked = FALSE,
        $text = NULL,
        $type = NULL,
        $wClickMethod = NULL,
        $wClickParam = NULL,
        $action = NULL,
        $param = NULL,
        $link = NULL,
        $disabled = FALSE,
        $stopPropagation = FALSE,
    ) {
        $this->locked = $locked;
        $icon = $locked ? self::ICONS['locked'] : self::ICONS['unlocked'];

        parent::__construct($text, $type, $icon, $wClickMethod, $wClickParam, $action, $param, $link, $disabled, $stopPropagation);
    }
}

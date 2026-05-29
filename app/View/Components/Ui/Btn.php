<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Btn extends Component
{
    const BTN_COLORS = [
        'pri' => 'btn-primary',
        'sec' => 'btn-secondary',
        'suc' => 'btn-success',
        'dan' => 'btn-danger',
        'war' => 'btn-warning',
        'inf' => 'btn-info',
        'lig' => 'btn-light gray-border-color',
        'dar' => 'btn-dark',
        'lin' => 'btn-link'
    ];

    const BTN_SIZE = [
        'lg' => 'btn-lg',
        'sm' => 'btn-sm',
    ];

    const ICON_POSITION = [
        'start' => 'start',
        'end'   => 'end',
    ];

    public $text;
    public $type;
    public $btnColor = self::BTN_COLORS['pri'];
    public $btnSize = FALSE;
    public $iconName = FALSE;
    public $iconPosition = self::ICON_POSITION['start'];
    /**TODO: remove old properties from all instances */
    public $wClickMethod = NULL;
    public $wClickParam = NULL;
    /** */
    public $action = NULL;
    public $param = NULL;
    public $link;
    public $disabled = FALSE;
    public $stopPropagation = FALSE;
    /**
     * Create a new component instance.
     */
    public function __construct(
        $text = NULL,
        $type = NULL,
        $icon = NULL,
        $wClickMethod = NULL,
        $wClickParam = NULL,
        $action = NULL,
        $param = NULL,
        $link = NULL,
        $disabled = FALSE,
        $stopPropagation = FALSE,
    ) {
        $this->setBtnType($type)->setIcon($icon);
        $this->text = $text != NULL ? $text : NULL;
        $this->wClickMethod = $wClickMethod;
        $this->wClickParam = $wClickParam;
        $this->action = $action;
        $this->param = $param;
        $this->link = $link;
        $this->disabled = $disabled;
        $this->stopPropagation = $stopPropagation;
    }

    private function setBtnType($type = NULL)
    {
        if (is_null($type)) return $this;

        $att = explode('.', $type);
        if (($att[0] != "" || $att[0] != NULL) && array_key_exists($att[0], self::BTN_COLORS)) $this->btnColor = self::BTN_COLORS[$att[0]];
        if (isset($att[1])) {
            if (($att[1] != "" || $att[1] != NULL) && array_key_exists($att[1], self::BTN_SIZE)) $this->btnSize = self::BTN_SIZE[$att[1]];
        }
        return $this;
    }

    private function setParams($params)
    {
        return explode(', ', $params);
    }

    private function setIcon($icon = NULL)
    {
        if (is_null($icon)) return;

        $att = explode('.', $icon);
        if (($att[0] != "" || $att[0] != NULL)) $this->iconName = $att[0];
        if (isset($att[1])) {
            if (($att[1] != "" || $att[1] != NULL) && array_key_exists($att[1], self::ICON_POSITION)) $this->iconPosition = self::ICON_POSITION[$att[1]];
        }
        return;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.btn');
    }
}

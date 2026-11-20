<?php

namespace App\Livewire;

use App\Exceptions\MissingMethodException;
use App\Exceptions\MissingPropertyException;
use Exception;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Locked;

class AlertModal extends Component
{
    // All state is set server-side only (openAlertModal / closeAlert). Locked so a client can't
    // overwrite it via /livewire/update (e.g. title as array -> htmlspecialchars TypeError,
    // or arbitrary HTML into the raw-echoed message).
    #[Locked]
    public $size        = NULL;
    #[Locked]
    public $type        = NULL;

    const  SIZE_OPTIONS = ['sm', 'lg'];
    const  TYPE_OPTIONS = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];

    #[Locked]
    public $title   = 'Here goes custom title';
    #[Locked]
    public $message = 'Here goes custom message';

    #[Locked]
    public $show = FALSE;

    #[On('show-alert-modal')]
    public function openAlertModal($data = [])
    {
        if (!empty($data) && is_array($data)) {
            foreach ($data as $key => $value) {
                try {
                    if (!property_exists(get_class($this), $key)) {
                        throw new MissingPropertyException($key);
                    }
                    if (!method_exists(get_class($this), 'set' . ucfirst($key))) {
                        throw new MissingMethodException('set' . ucfirst($key));
                    }
                    $method = 'set' . ucfirst($key);
                    $this->$method($value);
                } catch (Exception $e) {
                    return $this->dispatch('show-exception-modal', $e->getMessage());
                }
            }
            $this->show = TRUE;
        }
    }

    protected function setMessage($value)
    {
        return $this->message = $value;
    }

    protected function setTitle($value)
    {
        return $this->title = $value;
    }

    protected function setSize($value)
    {
        if (in_array($value, self::SIZE_OPTIONS)) {
            return $this->size = $value;
        }
        return $this->size = NULL;
    }

    protected function setType($value)
    {
        if (in_array($value, self::TYPE_OPTIONS)) {
            return $this->type = $value;
        }
        return $this->type = NULL;
    }

    public function closeAlert()
    {
        return $this->show = FALSE;
    }

    public function render()
    {
        return view('livewire.alert-modal');
    }
}

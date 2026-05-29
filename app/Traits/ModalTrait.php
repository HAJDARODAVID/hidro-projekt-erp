<?php

namespace App\Traits;

trait ModalTrait
{
    public $modalStatus = FALSE;

    public $displayIcon = TRUE;

    protected $blockModelOpening = false;

    public function openModal(): void
    {
        if ($this->blockModelOpening == false) {
            if (method_exists($this, 'beforeOpenModal')) $this->beforeOpenModal();
            $this->modalStatus = TRUE;
        }
        $this->blockModelOpening = false;
    }

    public function closeModal(): void
    {
        if (method_exists($this, 'beforeCloseModal')) $this->beforeCloseModal();
        $this->modalStatus = FALSE;
    }

    /**
     * Add a flag if to block the openModal action
     */
    protected function blockModelOpening()
    {
        $this->blockModelOpening = TRUE;
        return $this;
    }
}

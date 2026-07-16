<?php

namespace App\Services\Application\GlobalModal;

use InvalidArgumentException;

class GlobalModalService
{
    /**
     * Build a ModalDto from the `global-modal` config entry matching the given name.
     */
    public function make(string $name): ModalDto
    {
        $config = config("global-modal.{$name}");

        if (!is_array($config)) {
            throw new InvalidArgumentException("Unknown global modal: {$name}");
        }

        if (empty($config['component-path'])) {
            throw new InvalidArgumentException("Global modal [{$name}] is missing a component-path.");
        }

        $modal = (new ModalDto())
            ->setName($name)
            ->setComponentPath($config['component-path'] ?? '');

        if (!empty($config['header-name'])) {
            $modal->setHeaderName($config['header-name']);
        }

        if (!empty($config['header-style'])) {
            $modal->setHeaderStyle($config['header-style']);
        }

        if (!empty($config['max-width'])) {
            $modal->setMaxWidth($config['max-width']);
        }

        $modal->setStable((bool) ($config['stable'] ?? false));

        return $modal;
    }
}

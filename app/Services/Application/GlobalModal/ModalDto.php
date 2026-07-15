<?php

namespace App\Services\Application\GlobalModal;

use App\Services\BaseDTO;

class ModalDto extends BaseDTO
{
    protected string|null $name;
    protected string|null $componentPath;
    protected string|null $headerName;
    protected string|null $headerStyle;
    protected string|null $maxWidth;

    public function __construct(
        string|null $name = null,
        string|null $componentPath = null,
        string $headerName = 'Modal',
        string|null $headerStyle = 'font-weight: 600; font-size: 1rem;',
        string|null $maxWidth = '1140px',
        // string $headerStyle = 'font-weight: 600; font-size: 1rem;',
        // string $maxWidth = '1140px'
    ) {
        $this->name = $name;
        $this->componentPath = $componentPath;
        $this->headerName = $headerName;
        $this->headerStyle = $headerStyle;
        $this->maxWidth = $maxWidth;

        // $this->headerStyle = $headerStyle;
        // $this->maxWidth = $maxWidth;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getComponentPath(): string|null
    {
        return $this->componentPath;
    }

    public function setComponentPath(string $componentPath): self
    {
        $this->componentPath = $componentPath;

        return $this;
    }

    public function getHeaderName(): string
    {
        return $this->headerName;
    }

    public function setHeaderName(string $headerName): self
    {
        $this->headerName = $headerName;

        return $this;
    }

    public function getHeaderStyle(): string
    {
        return $this->headerStyle;
    }

    public function setHeaderStyle(string $headerStyle): self
    {
        $this->headerStyle = $headerStyle;

        return $this;
    }

    public function getMaxWidth(): string
    {
        return $this->maxWidth;
    }

    public function setMaxWidth(string $maxWidth): self
    {
        $this->maxWidth = $maxWidth;

        return $this;
    }
}

<?php

namespace App\Services;

/**
 * BaseService
 * Provides common functionality for handling service responses, 
 * success flags, and error messages across all specific services.
 */
class BaseService
{
    /**
     * @var bool Flag indicating success or failure.
     */
    protected bool $success = false;

    /**
     * @var string The message associated with the outcome.
     */
    protected string $message = '';

    /**
     * @var mixed The data payload to be returned.
     */
    protected mixed $data = [];

    /**
     * Sets a custom error message.
     *
     * @param string $message
     * @return $this
     */
    protected function setErrorMessage(string $message): self
    {
        $this->success = false;
        $this->message = $message;
        return $this;
    }

    /**
     * Sets a custom success message and optionally the data payload.
     *
     * @param string $message
     * @param mixed $data
     * @return $this
     */
    protected function setSuccessMessage(string $message, mixed $data = []): self
    {
        $this->success = true;
        $this->message = $message;
        $this->data = $data;
        return $this;
    }

    /**
     * Sets the data payload.
     *
     * @param mixed $data
     * @return $this
     */
    protected function setData($data): self
    {
        $this->success = true;
        $this->data = $data;
        return $this;
    }

    /**
     * Sets the success status to TRUE without the message.
     *
     * @return $this
     */
    protected function setSuccessTrue(): self
    {
        $this->success = true;
        return $this;
    }

    /**
     * Sets the success status to FALSE without the message.
     *
     * @return $this
     */
    protected function setSuccessFalse(): self
    {
        $this->success = false;
        return $this;
    }

    /**
     * Get the success status.
     * @return bool
     */
    public function getResponseStatus(): bool
    {
        return  $this->success;
    }

    /**
     * Gets the complete structured response mixed.
     * @return array
     */
    public function getResponse(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }
}

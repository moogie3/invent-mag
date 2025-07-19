<?php

namespace App\DTOs;

use Carbon\Carbon;

class TransactionDTO
{
    public $id;
    public $type;
    public $invoice;
    public $customer_supplier;
    public $contact_info;
    public $date;
    public $amount;
    public $paid_amount;
    public $due_amount;
    public $status;
    public $view_url;
    public $edit_url;

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getKey()
    {
        return $this->id;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }

    public function __toString()
    {
        return (string) $this->id;
    }
}

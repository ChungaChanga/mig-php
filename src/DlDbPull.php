<?php

namespace Andrey\PhpMig;

class DlDbPull
{
    private LagacyDlDb $lagacyDlDb;
    private NewDlDb $newDlDb;
    public function __construct(LagacyDlDb $lagacyDlDb, NewDlDb $newDlDb)
    {
        $this->lagacyDlDb = $lagacyDlDb;
        $this->newDlDb = $newDlDb;
        $this->lagacyDlDb->init();
        $this->newDlDb->init();
    }
    function fetchBillingAddress(string $ccToken, int $customerId): ?Address
    {
        $addr = $this->lagacyDlDb->fetchBillingAddressByOrderMetaToken($ccToken);
        if ($addr->isFull()) {
            return $addr;
        }
        $addr = $this->newDlDb->fetchBillingAddressFromNewDl($customerId);
        if ($addr->isFull()) {
             return $addr;
        }
        $addr = $this->lagacyDlDb->fetchBillingAddressByLastOrderMeta($customerId);
        if ($addr->isFull()) {
            return $addr;
        }
        $addr = $this->lagacyDlDb->fetchBillingAddressFromUserMeta($customerId);
        if ($addr->isFull()) {
            return $addr;
        }
        return null;
    }
}
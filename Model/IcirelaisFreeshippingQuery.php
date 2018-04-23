<?php

namespace DpdPickup\Model;

use DpdPickup\Model\Base\IcirelaisFreeshippingQuery as BaseIcirelaisFreeshippingQuery;

/**
 * Skeleton subclass for performing query and update operations on the 'icirelais_freeshipping' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class IcirelaisFreeshippingQuery extends BaseIcirelaisFreeshippingQuery
{
    public function getLast()
    {
        return $this->orderById('desc')->findOne()->getActive();
    }
}
// IcirelaisFreeshippingQuery

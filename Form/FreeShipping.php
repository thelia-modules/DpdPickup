<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia                                                                       */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*      along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace DpdPickup\Form;

use DpdPickup\DpdPickup;
use DpdPickup\Model\IcirelaisFreeshippingQuery;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

class FreeShipping extends BaseForm
{
    protected function buildForm()
    {
        $freeshipping = IcirelaisFreeshippingQuery::create()->getLast();

        $this->formBuilder
            ->add(
                "freeshipping",
                "checkbox",
                array(
                    'data' => $freeshipping,
                    'label' => Translator::getInstance()->trans("Activate free shipping: ", [], DpdPickup::DOMAIN)
                )
            );
    }

    /**
     * @return string the name of you form. This name must be unique
     */
    public function getName()
    {
        return "icirelaisfreeshipping";
    }
}

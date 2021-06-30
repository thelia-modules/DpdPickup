<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
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
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace DpdPickup\Controller;

use Thelia\Controller\Front\BaseFrontController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/module/dpdpickup", name="module_dpdpickup")
 * Class SearchCityController
 * @package DpdPickup\Controller
 * @author Thelia <info@thelia.net>
 */
class SearchCityController extends BaseFrontController
{
    /**
     * @Route("/{zipcode}/{city}", name="_search", methods="GET")
     */
    public function searchAction($zipcode, $city)
    {
        return $this->render("getSpecificLocation", array("_zipcode_"=>$zipcode, "_city_"=>$city));
    }
}

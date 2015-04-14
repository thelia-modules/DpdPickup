
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- order_address_icirelais
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `order_address_icirelais`
(
    `id` INTEGER NOT NULL,
    `code` VARCHAR(10) NOT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_order_address_icirelais_order_address_id`
        FOREIGN KEY (`id`)
        REFERENCES `order_address` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- address_icirelais
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `address_icirelais`
(
    `id` INTEGER NOT NULL,
    `title_id` INTEGER NOT NULL,
    `company` VARCHAR(255),
    `firstname` VARCHAR(255) NOT NULL,
    `lastname` VARCHAR(255) NOT NULL,
    `address1` VARCHAR(255) NOT NULL,
    `address2` VARCHAR(255) NOT NULL,
    `address3` VARCHAR(255) NOT NULL,
    `zipcode` VARCHAR(10) NOT NULL,
    `city` VARCHAR(255) NOT NULL,
    `country_id` INTEGER NOT NULL,
    `code` VARCHAR(10) NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `FI_address_icirelais_customer_title_id` (`title_id`),
    INDEX `FI_address_country_id` (`country_id`),
    CONSTRAINT `fk_address_icirelais_customer_title_id`
        FOREIGN KEY (`title_id`)
        REFERENCES `customer_title` (`id`)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,
    CONSTRAINT `fk_address_icirelais_country_id`
        FOREIGN KEY (`country_id`)
        REFERENCES `country` (`id`)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- icirelais_freeshipping
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `icirelais_freeshipping`;

CREATE TABLE `icirelais_freeshipping`
(
  `id` INTEGER NOT NULL AUTO_INCREMENT,
  `active` TINYINT(1) NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `icirelais_freeshipping`(`active`, `created_at`, `updated_at`) VALUES(0, NOW(), NOW());

-- ---------------------------------------------------------------------
-- Mail templates for icirelais
-- ---------------------------------------------------------------------
-- First, delete existing entries
SET @var := 0;
SELECT @var := `id` FROM `message` WHERE name="order_confirmation_icirelais";
DELETE FROM `message` WHERE `id`=@var;
-- Try if ON DELETE constraint isn't set
DELETE FROM `message_i18n` WHERE `id`=@var;

-- Then add new entries
SELECT @max := MAX(`id`) FROM `message`;
SET @max := @max+1;
-- insert message
INSERT INTO `message` (`id`, `name`, `secured`) VALUES
  (@max,
   'order_confirmation_icirelais',
   '0'
  );
-- and template fr_FR
INSERT INTO `message_i18n` (`id`, `locale`, `title`, `subject`, `text_message`, `html_message`) VALUES
(@max,
'fr_FR',
   'order confirmation icirelais',
   'Livraison de la commande : {$order_ref}',
   '{assign var="order_id" value=1}\r\n{loop name="order.invoice" type="order" id=$order_id customer="*"}\r\n    {loop name="currency.order" type="currency" id=$CURRENCY}\r\n        {assign "orderCurrency" $CODE}\r\n    {/loop}\r\n{loop type="order_address" name="delivery_address" id=$DELIVERY_ADDRESS}\r\n{loop type="title" name="order-invoice-address-title" id=$TITLE}{$LONG}{/loop}{$FIRSTNAME} {$LASTNAME}\\r\\n\r\n{$ADDRESS1} {$ADDRESS2} {$ADDRESS3}\\r\\n\r\n{$ZIPCODE} {$CITY}\\r\\n\r\n{loop type="country" name="country_delivery" id=$COUNTRY}{$TITLE}{/loop}\\r\\n\r\n{/loop}\r\nConfirmation de commande {$REF} du {format_date date=$INVOICE_DATE}\\r\\n\\r\\n\r\nLes articles commandés:\\r\\n\r\n{loop type="order_product" name="order-products" order=$ID}\r\n{if $WAS_IN_PROMO == 1}\r\n    {assign "realPrice" $PROMO_PRICE}\r\n    {assign "realTax" $PROMO_PRICE_TAX}\r\n    {assign "realTaxedPrice" $TAXED_PROMO_PRICE}\r\n{else}\r\n    {assign "realPrice" $PRICE}\r\n    {assign "realTax" $PRICE_TAX}\r\n    {assign "realTaxedPrice" $TAXED_PRICE}\r\n{/if}\r\n    \\r\\n\r\n    Article : {$TITLE}\r\n{ifloop rel="combinations"}\r\n    {loop type="order_product_attribute_combination" name="combinations" order_product=$ID}\r\n    {$ATTRIBUTE_TITLE} - {$ATTRIBUTE_AVAILABILITY_TITLE}\\r\\n\r\n{/loop}\r\n{/ifloop}\\r\\n\r\n    Quantité : {$QUANTITY}\\r\\n\r\n    Prix unitaire TTC : {$realTaxedPrice} {$orderCurrency}\\r\\n\r\n{/loop}\r\n\\r\\n-----------------------------------------\\r\\n\r\nMontant total TTC :    {$TOTAL_TAXED_AMOUNT - $POSTAGE} {$orderCurrency} \\r\\n\r\nFrais de port TTC :    {$POSTAGE} {$orderCurrency} \\r\\n\r\nSomme totale:            {$TOTAL_TAXED_AMOUNT} {$orderCurrency} \\r\\n\r\n==================================\\r\\n\\r\\n\r\nVotre facture est disponible dans la rubrique mon compte sur {config key="url_site"}\r\n{loop name="tracking" type="dpdpickup.urltracking" ref=$REF}\r\nVous pouvez suivre votre colis à l''adresse suivante: {$URL}\r\n{/loop}\r\n{/loop}',
   '{loop name="order.invoice" type="order" id=$order_id customer="*"}\r\n    {loop name="currency.order" type="currency" id=$CURRENCY}\r\n        {assign "orderCurrency" $SYMBOL}\r\n    {/loop}\r\n{loop type="customer" name="customer.invoice" id=$CUSTOMER current="0"}\r\n    {assign var="customer_ref" value=$REF}\r\n{/loop}\r\n<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"\r\n        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">\r\n<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="fr">\r\n<head>\r\n    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>\r\n    <title>courriel de confirmation de commande de {config key="url_site"} </title>\r\n    {literal}\r\n    <style type="text/css">body {font-family: Arial, Helvetica, sans-serif; font-size:100%; text-align:center;}#liencompte {margin:15px 0 ; text-align:center; font-size:10pt;}#wrapper {width:480pt;margin:0 auto;}#entete {padding-bottom:20px;margin-bottom:10px;border-bottom:1px dotted #000;}#logotexte {float:left;width:180pt;height:75pt;border:1pt solid #000;font-size:18pt;text-align:center;}#logoimg{float:left;}#h2 {margin:0;padding:0;font-size:140%;text-align:center;}#h3 {margin:0;padding:0;font-size:120%;text-align:center;}#tableprix {margin:0 auto;border-collapse:collapse;font-size:80%;}#intitules {font-weight:bold;text-align:center;}#ref {width:65pt;border:1px solid #000;}#designation {width:278pt;border:1px solid #000;}#pu {width:65pt;border:1px solid #000;}#qte {width:60pt;border:1px solid #000;}.ligneproduit{font-weight:normal;}.cellref{text-align:right;padding-right:6pt;border:1px solid #000;}.celldsg{text-align:left;padding-left:6pt;border:1px solid #000;}.cellpu{text-align:right;padding-right:6pt;border:1px solid #000;}.cellqte{text-align:right;padding-right:6pt;border:1px solid #000;}.lignevide{border-bottom:1px solid #000;}.totauxtitre{text-align:right;padding-right:6pt;border-left:1px solid #000;}.totauxcmdtitre{text-align:right;padding-right:6pt;border-left:1px solid #000;border-bottom:1px solid #000;}.totauxprix{text-align:right;padding-right:6pt;border:1px solid #000;}.blocadresses{display:inline;float:left;width:228pt;margin:12pt 4pt 12pt 5pt;font-size:80%;line-height:18pt;text-align:left;border:1px solid #000;}.stylenom{margin:0;padding:0 0 0 10pt;border-bottom:1px solid #000;}.styleliste{margin:0;padding:0 0 0 10pt;}</style>\r\n    {/literal}\r\n</head>\r\n<body>\r\n<div id="wrapper">\r\n    <div id="entete"><h1 id="logotexte">{config key="store_name"}</h1>\r\n        <h2 id="info">Confirmation de commande</h2>\r\n        <h3 id="commande">N&deg; {$REF} du <span style="font-size:80%">{format_date date=$INVOICE_DATE output="date"}</span></h3>\r\n    </div>\r\n    <table id="tableprix" border="0">\r\n        <tbody>\r\n        <tr id="intitules">\r\n            <th id="ref">R&eacute;f&eacute;rence</th>\r\n            <th id="designation">D&eacute;signation</th>\r\n            <th id="pu">P.U. &euro;</th>\r\n            <th id="qte">Qt&eacute;</th>\r\n        </tr>\r\n        {loop type="order_product" name="order-products" order=$ID}\r\n        {if $WAS_IN_PROMO == 1}\r\n            {assign "realPrice" $PROMO_PRICE}\r\n            {assign "realTax" $PROMO_PRICE_TAX}\r\n            {assign "realTaxedPrice" $TAXED_PROMO_PRICE}\r\n        {else}\r\n            {assign "realPrice" $PRICE}\r\n            {assign "realTax" $PRICE_TAX}\r\n            {assign "realTaxedPrice" $TAXED_PRICE}\r\n        {/if}\r\n            <tr class="ligneproduit">\r\n                <td class="cellref">{$REF}</td>\r\n                <td class="celldsg">{$TITLE}\r\n                    {ifloop rel="combinations"}\r\n                        {loop type="order_product_attribute_combination" name="combinations" order_product=$ID}\r\n                            {$ATTRIBUTE_TITLE} - {$ATTRIBUTE_AVAILABILITY_TITLE}<br>\r\n                        {/loop}\r\n                    {/ifloop}\r\n                </td>\r\n                <td class="cellpu">{$orderCurrency} {$realTaxedPrice}</td>\r\n                <td class="cellqte">{$QUANTITY}</td>\r\n            </tr>\r\n        {/loop}\r\n        <!-- insere une ligne vide -->\r\n        <tr class="ligneproduit">\r\n            <td class="lignevide" colspan="4">&nbsp;</td>\r\n        </tr>\r\n        <tr class="ligneproduit">\r\n            <td class="totauxtitre" colspan="3">Montant total avant remise &euro;</td>\r\n            <td class="totauxprix">{$orderCurrency} {$TOTAL_TAXED_AMOUNT - $POSTAGE}</td>\r\n        </tr>\r\n        <tr class="ligneproduit">\r\n            <td class="totauxtitre" colspan="3">Port &euro;</td>\r\n            <td class="totauxprix">{$orderCurrency} {$POSTAGE}</td>\r\n        </tr>\r\n        <tr class="ligneproduit">\r\n            <td class="totauxcmdtitre" colspan="3">Montant total de la commande &euro;</td>\r\n            <td class="totauxprix">{$orderCurrency} {$TOTAL_TAXED_AMOUNT}</td>\r\n        </tr>\r\n        </tbody>\r\n    </table>\r\n    <div class="blocadresses">\r\n        <p class="stylenom">LIVRAISON : {loop name="delivery-module" type="module" id=$DELIVERY_MODULE}{$TITLE}{/loop}</p>\r\n    {loop type="order_address" name="delivery_address" id=$DELIVERY_ADDRESS}\r\n        <p class="styleliste">N&deg; de client : {$customer_ref}</p>\r\n        <p class="styleliste">Nom :\r\n            {loop type="title" name="order-invoice-address-title" id=$TITLE}{$LONG}{/loop} {$FIRSTNAME} {$LASTNAME}</p>\r\n        <p class="styleliste">N&deg; et rue :\r\n            {$ADDRESS1}</p>\r\n        <p class="styleliste">Compl&eacute;ment : {$ADDRESS2}\r\n            {$ADDRESS3}</p>\r\n        <p class="styleliste">Code postal : {$ZIPCODE}</p>\r\n        <p class="styleliste">Ville : {$CITY}</p>\r\n        <p class="styleliste">Pays : {loop type="country" name="country_delivery" id=$COUNTRY}{$TITLE}{/loop}</p>\r\n    </div>\r\n    {/loop}\r\n    <div class="blocadresses">\r\n        <p class="stylenom">FACTURATION : paiement par {loop name="payment-module" type="module" id=$PAYMENT_MODULE}{$TITLE}{/loop}</p>\r\n    {loop type="order_address" name="delivery_address" id=$INVOICE_ADDRESS}\r\n        <p class="styleliste">N&deg; de client : {$customer_ref}</p>\r\n        <p class="styleliste">Nom :\r\n            {loop type="title" name="order-invoice-address-title" id=$TITLE}{$LONG}{/loop} {$FIRSTNAME} {$LASTNAME}</p>\r\n        <p class="styleliste">N&deg; et rue :\r\n            {$ADDRESS1}</p>\r\n        <p class="styleliste">Compl&eacute;ment : {$ADDRESS2}\r\n            {$ADDRESS3}</p>\r\n        <p class="styleliste">Code postal : {$ZIPCODE}</p>\r\n        <p class="styleliste">Ville : {$CITY}</p>\r\n        <p class="styleliste">Pays : {loop type="country" name="country_delivery" id=$COUNTRY}{$TITLE}{/loop}</p>\r\n    </div>\r\n    {/loop}\r\n    <p id="liencompte">Le suivi de votre commande est disponible dans la rubrique mon compte sur <a href="{config key="url_site"}">{config key="url_site"}</a></p>\r\n    {loop name="tracking" type="dpdpickup.urltracking" ref=$REF}\r\n    <p>Vous pouvez suivre votre colis <a href="{$URL}">ici</a></p>\r\n    {/loop}\r\n</div>\r\n</body>\r\n</html>\r\n{/loop}'
  );
-- and en_US
INSERT INTO `message_i18n` (`id`, `locale`, `title`, `subject`, `text_message`, `html_message`) VALUES
  (@max,
   'en_US',
   'order confirmation icirelais',
   'Livraison de la commande : {$order_ref}',
   '{assign var="order_id" value=1}\r\n{loop name="order.invoice" type="order" id=$order_id customer="*"}\r\n    {loop name="currency.order" type="currency" id=$CURRENCY}\r\n        {assign "orderCurrency" $CODE}\r\n    {/loop}\r\n{loop type="order_address" name="delivery_address" id=$DELIVERY_ADDRESS}\r\n{loop type="title" name="order-invoice-address-title" id=$TITLE}{$LONG}{/loop}{$FIRSTNAME} {$LASTNAME}\\r\\n\r\n{$ADDRESS1} {$ADDRESS2} {$ADDRESS3}\\r\\n\r\n{$ZIPCODE} {$CITY}\\r\\n\r\n{loop type="country" name="country_delivery" id=$COUNTRY}{$TITLE}{/loop}\\r\\n\r\n{/loop}\r\nConfirmation de commande {$REF} du {format_date date=$INVOICE_DATE}\\r\\n\\r\\n\r\nLes articles commandés:\\r\\n\r\n{loop type="order_product" name="order-products" order=$ID}\r\n{if $WAS_IN_PROMO == 1}\r\n    {assign "realPrice" $PROMO_PRICE}\r\n    {assign "realTax" $PROMO_PRICE_TAX}\r\n    {assign "realTaxedPrice" $TAXED_PROMO_PRICE}\r\n{else}\r\n    {assign "realPrice" $PRICE}\r\n    {assign "realTax" $PRICE_TAX}\r\n    {assign "realTaxedPrice" $TAXED_PRICE}\r\n{/if}\r\n    \\r\\n\r\n    Article : {$TITLE}\r\n{ifloop rel="combinations"}\r\n    {loop type="order_product_attribute_combination" name="combinations" order_product=$ID}\r\n    {$ATTRIBUTE_TITLE} - {$ATTRIBUTE_AVAILABILITY_TITLE}\\r\\n\r\n{/loop}\r\n{/ifloop}\\r\\n\r\n    Quantité : {$QUANTITY}\\r\\n\r\n    Prix unitaire TTC : {$realTaxedPrice} {$orderCurrency}\\r\\n\r\n{/loop}\r\n\\r\\n-----------------------------------------\\r\\n\r\nMontant total TTC :    {$TOTAL_TAXED_AMOUNT - $POSTAGE} {$orderCurrency} \\r\\n\r\nFrais de port TTC :    {$POSTAGE} {$orderCurrency} \\r\\n\r\nSomme totale:            {$TOTAL_TAXED_AMOUNT} {$orderCurrency} \\r\\n\r\n==================================\\r\\n\\r\\n\r\nVotre facture est disponible dans la rubrique mon compte sur {config key="url_site"}\r\n{loop name="tracking" type="dpdpickup.urltracking" ref=$REF}\r\nVous pouvez suivre votre colis à l''adresse suivante: {$URL}\r\n{/loop}\r\n{/loop}',
   '{loop name="order.invoice" type="order" id=$order_id customer="*"}\r\n    {loop name="currency.order" type="currency" id=$CURRENCY}\r\n        {assign "orderCurrency" $SYMBOL}\r\n    {/loop}\r\n{loop type="customer" name="customer.invoice" id=$CUSTOMER current="0"}\r\n    {assign var="customer_ref" value=$REF}\r\n{/loop}\r\n<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"\r\n        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">\r\n<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="fr">\r\n<head>\r\n    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>\r\n    <title>courriel de confirmation de commande de {config key="url_site"} </title>\r\n    {literal}\r\n    <style type="text/css">body {font-family: Arial, Helvetica, sans-serif; font-size:100%; text-align:center;}#liencompte {margin:15px 0 ; text-align:center; font-size:10pt;}#wrapper {width:480pt;margin:0 auto;}#entete {padding-bottom:20px;margin-bottom:10px;border-bottom:1px dotted #000;}#logotexte {float:left;width:180pt;height:75pt;border:1pt solid #000;font-size:18pt;text-align:center;}#logoimg{float:left;}#h2 {margin:0;padding:0;font-size:140%;text-align:center;}#h3 {margin:0;padding:0;font-size:120%;text-align:center;}#tableprix {margin:0 auto;border-collapse:collapse;font-size:80%;}#intitules {font-weight:bold;text-align:center;}#ref {width:65pt;border:1px solid #000;}#designation {width:278pt;border:1px solid #000;}#pu {width:65pt;border:1px solid #000;}#qte {width:60pt;border:1px solid #000;}.ligneproduit{font-weight:normal;}.cellref{text-align:right;padding-right:6pt;border:1px solid #000;}.celldsg{text-align:left;padding-left:6pt;border:1px solid #000;}.cellpu{text-align:right;padding-right:6pt;border:1px solid #000;}.cellqte{text-align:right;padding-right:6pt;border:1px solid #000;}.lignevide{border-bottom:1px solid #000;}.totauxtitre{text-align:right;padding-right:6pt;border-left:1px solid #000;}.totauxcmdtitre{text-align:right;padding-right:6pt;border-left:1px solid #000;border-bottom:1px solid #000;}.totauxprix{text-align:right;padding-right:6pt;border:1px solid #000;}.blocadresses{display:inline;float:left;width:228pt;margin:12pt 4pt 12pt 5pt;font-size:80%;line-height:18pt;text-align:left;border:1px solid #000;}.stylenom{margin:0;padding:0 0 0 10pt;border-bottom:1px solid #000;}.styleliste{margin:0;padding:0 0 0 10pt;}</style>\r\n    {/literal}\r\n</head>\r\n<body>\r\n<div id="wrapper">\r\n    <div id="entete"><h1 id="logotexte">{config key="store_name"}</h1>\r\n        <h2 id="info">Confirmation de commande</h2>\r\n        <h3 id="commande">N&deg; {$REF} du <span style="font-size:80%">{format_date date=$INVOICE_DATE output="date"}</span></h3>\r\n    </div>\r\n    <table id="tableprix" border="0">\r\n        <tbody>\r\n        <tr id="intitules">\r\n            <th id="ref">R&eacute;f&eacute;rence</th>\r\n            <th id="designation">D&eacute;signation</th>\r\n            <th id="pu">P.U. &euro;</th>\r\n            <th id="qte">Qt&eacute;</th>\r\n        </tr>\r\n        {loop type="order_product" name="order-products" order=$ID}\r\n        {if $WAS_IN_PROMO == 1}\r\n            {assign "realPrice" $PROMO_PRICE}\r\n            {assign "realTax" $PROMO_PRICE_TAX}\r\n            {assign "realTaxedPrice" $TAXED_PROMO_PRICE}\r\n        {else}\r\n            {assign "realPrice" $PRICE}\r\n            {assign "realTax" $PRICE_TAX}\r\n            {assign "realTaxedPrice" $TAXED_PRICE}\r\n        {/if}\r\n            <tr class="ligneproduit">\r\n                <td class="cellref">{$REF}</td>\r\n                <td class="celldsg">{$TITLE}\r\n                    {ifloop rel="combinations"}\r\n                        {loop type="order_product_attribute_combination" name="combinations" order_product=$ID}\r\n                            {$ATTRIBUTE_TITLE} - {$ATTRIBUTE_AVAILABILITY_TITLE}<br>\r\n                        {/loop}\r\n                    {/ifloop}\r\n                </td>\r\n                <td class="cellpu">{$orderCurrency} {$realTaxedPrice}</td>\r\n                <td class="cellqte">{$QUANTITY}</td>\r\n            </tr>\r\n        {/loop}\r\n        <!-- insere une ligne vide -->\r\n        <tr class="ligneproduit">\r\n            <td class="lignevide" colspan="4">&nbsp;</td>\r\n        </tr>\r\n        <tr class="ligneproduit">\r\n            <td class="totauxtitre" colspan="3">Montant total avant remise &euro;</td>\r\n            <td class="totauxprix">{$orderCurrency} {$TOTAL_TAXED_AMOUNT - $POSTAGE}</td>\r\n        </tr>\r\n        <tr class="ligneproduit">\r\n            <td class="totauxtitre" colspan="3">Port &euro;</td>\r\n            <td class="totauxprix">{$orderCurrency} {$POSTAGE}</td>\r\n        </tr>\r\n        <tr class="ligneproduit">\r\n            <td class="totauxcmdtitre" colspan="3">Montant total de la commande &euro;</td>\r\n            <td class="totauxprix">{$orderCurrency} {$TOTAL_TAXED_AMOUNT}</td>\r\n        </tr>\r\n        </tbody>\r\n    </table>\r\n    <div class="blocadresses">\r\n        <p class="stylenom">LIVRAISON : {loop name="delivery-module" type="module" id=$DELIVERY_MODULE}{$TITLE}{/loop}</p>\r\n    {loop type="order_address" name="delivery_address" id=$DELIVERY_ADDRESS}\r\n        <p class="styleliste">N&deg; de client : {$customer_ref}</p>\r\n        <p class="styleliste">Nom :\r\n            {loop type="title" name="order-invoice-address-title" id=$TITLE}{$LONG}{/loop} {$FIRSTNAME} {$LASTNAME}</p>\r\n        <p class="styleliste">N&deg; et rue :\r\n            {$ADDRESS1}</p>\r\n        <p class="styleliste">Compl&eacute;ment : {$ADDRESS2}\r\n            {$ADDRESS3}</p>\r\n        <p class="styleliste">Code postal : {$ZIPCODE}</p>\r\n        <p class="styleliste">Ville : {$CITY}</p>\r\n        <p class="styleliste">Pays : {loop type="country" name="country_delivery" id=$COUNTRY}{$TITLE}{/loop}</p>\r\n    </div>\r\n    {/loop}\r\n    <div class="blocadresses">\r\n        <p class="stylenom">FACTURATION : paiement par {loop name="payment-module" type="module" id=$PAYMENT_MODULE}{$TITLE}{/loop}</p>\r\n    {loop type="order_address" name="delivery_address" id=$INVOICE_ADDRESS}\r\n        <p class="styleliste">N&deg; de client : {$customer_ref}</p>\r\n        <p class="styleliste">Nom :\r\n            {loop type="title" name="order-invoice-address-title" id=$TITLE}{$LONG}{/loop} {$FIRSTNAME} {$LASTNAME}</p>\r\n        <p class="styleliste">N&deg; et rue :\r\n            {$ADDRESS1}</p>\r\n        <p class="styleliste">Compl&eacute;ment : {$ADDRESS2}\r\n            {$ADDRESS3}</p>\r\n        <p class="styleliste">Code postal : {$ZIPCODE}</p>\r\n        <p class="styleliste">Ville : {$CITY}</p>\r\n        <p class="styleliste">Pays : {loop type="country" name="country_delivery" id=$COUNTRY}{$TITLE}{/loop}</p>\r\n    </div>\r\n    {/loop}\r\n    <p id="liencompte">Le suivi de votre commande est disponible dans la rubrique mon compte sur <a href="{config key="url_site"}">{config key="url_site"}</a></p>\r\n    {loop name="tracking" type="dpdpickup.urltracking" ref=$REF}\r\n    <p>Vous pouvez suivre votre colis <a href="{$URL}">ici</a></p>\r\n    {/loop}\r\n</div>\r\n</body>\r\n</html>\r\n{/loop}'
  );


# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;

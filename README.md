# DPD Pickup
author: Thelia <info@thelia.net>

## fr_FR

### Installation

L'installation du module DpdPickup se fait de la même manière que les autres, vous pouvez soit importer directement le zip dans le back office,
soit le décompresser dans <dossier de Thélia2>/local/modules.

Il nous vous reste plus qu'à activer le module et à associer vos zones de livraison.

### Utilisation

Une page de configuration est mise à votre disposition pour vous permettre d'effectuer deux tâches:
	- exporter un fichier EXAPRINT (export.dat) contenant les informations sur les livraisons effectuées via DPD Pickup
	- configurer les tranches de prix des livraisons par DPD Pickup

Pour vous y rendre, il vous suffit d'aller dans le back Office, onglet "Modules" et de cliquer sur "Configurer" sur la ligne du module DPD Pickup.
Pour exporter un fichier EXAPRINT, il faut renseigner tous les champs présents dans le formulaire.

### Intégration

Le module utilise les hooks, vous n'aurez en principe rien à faire si votre template intègre bien tous les hooks de Thelia.

Si vous souhaitez faire votre propre intégration, suivez le détail ci-dessous :

Pour l'exemple d'intégration, j'ai utilisé une google map, ceci n'est pas nécessaire mais préférable.
En effet, le module n'interagit pas avec pendant la commande.
Une fois le module activé, il devient néanmoins indispensable de transmettre une variable $_POST['pr_code'] dans le formulaire "thelia.order.delivery",
sinon, vous ne pourrez plus passer à l'étape 3 ( order-invoice ).
De plus, une boucle "delivery.dpdpickup" est disponible et doit remplacer la boucle "delivery" dans order-delivery-module-list.html,
les deux sont semblable, mais delivery.dpdpickup possède une variable en plus, qui permet de savoir si le module est ou non DpdPickup ( ce qui permet une intégration spécifique
de la ligne DpdPickup).
La variable "pr_code" doit contenir l'identifiant du point relais choisi par l'utilisateur.
Une boucle vous est fournie pour obtenir les 10 points relais les plus proches de l'adresse par défault de l'utilisateur: dpdpickup.relais.around
Sinon, une route est disponible pour obtenir 10 points relais dans une ville: /module/dpdpickup/{ville}/{code postal}
Cette route pointe vers le controlleur "SearchCityController" qui génère un fichier json, que vous pouvez utiliser, par exemple, avec jquery/ajax.

Pour afficher l'adresse du point relais en adresse de livraison sur la page order-invoice.html, 
il vous suffit de replacer le type de la boucle nommée "delivery-address" en address.dpdpickup, à la place de "delivery"

Pour rajouter l'adresse de suivi du colis dans le mail de confirmation de la commande, une boucle est mise à votre disposition: "dpdpickup.urltracking"
elle prend un argument ref, qui est la référence de la commande, et une sortie $URL.
Si l'url ne peut être générée, elle ne renvoie rien.
On peut donc l'intégrer de la manière suivante:

{loop name="tracking" type="dpdpickup.urltracking" ref=$REF}
Vous pouvez suivre votre colis <a href="{$URL}">ici</a>
{/loop}

## en_US

### Install notes

The install process of DpdPickup module is the same than the other modules, you can import it directly from the back office,
or unzip it in <path to thelia2>/local/modules.

Then you can activate DPD Pickup module and configure you shipping zones.

### How to use

A configuration page is provided with the module, so you can:
	- export an EXAPRINT file (export.dat), with informations on all deliveries done with DPD Pickup
	- configure price slices for shipping zones.

You can use it in the back office by going to "Modules" tab, then "configure" button on DPD Pickup' line.
For exporting an EXAPRINT file, you must complete the entire form.

### Integration

This module uses native hooks. If your template use them you have nothing to do.

If you want to do your own integration, follow the description below :

For the integration example, I used a google map, but it's not necessary.
In fact, the module doesn't interact with the map during the order.
Once the module is active, you must create an input named "pr_code" in your form "thelia.order.delivery",
whereas you won't be able to go to step 3 ( order-invoice ).
Moreover, the loop "delivery.dpdpickup" is available and must replace "delivery" in order-delivery-module-list.html,
they do the same thing, but delivery.dpdpickup has a new variable that allows you to know if the delivery module that's being looped is DpdPickup.
The input "pr_code" must contain the ID of the pick-up & go store choosed by the user.
A loop is provided to get the 10 nearest pick-up & go stores of user's default address: dpdpickup.relais.around
There's also a route to get 10 pick-p & go stores in another city: /module/dpdpickup/{city}/{zipcode}
This route uses "SearchCityController" controller. It generate a json output, which you can use with, for example, jquery/ajax.

If you want to show the store's address as delivery address, you just have to replace the "delivery-address" loop type by address.dpdpickup

If you want to add the package tracking link to the order email, you can use the loop: "dpdpickup.urltracking"
It take only one argument ref, that is the order's reference, and it has one output $URL.
If the link can't be generated, there's no output.
You can, for exemple, integrate the link like that in the email:

{loop name="tracking" type="dpdpickup.urltracking" ref=$REF}
You can track your package <a href="{$URL}">here</a>
{/loop}
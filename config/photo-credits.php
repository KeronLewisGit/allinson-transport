<?php

/*
| Every photo on the site, keyed like config('allinson.photos'). Shown on /photo-credits.
| Checked 27 October 2026.
| - "own-*" photos are the firm's own, taken from allinsontransport.co.uk (its /cmsimages and
|   /images folders, also archived by the Wayback Machine from 2016). The firm commissioned
|   them for its site; confirm the photographer's licence covers reuse on the new site.
| - "a40" is a Wikimedia Commons photo of one of the firm's own lorries (CC BY-SA 2.0).
| - The rest are Wikimedia Commons photos that only illustrate the kind of work, and are
|   labelled "Illustrative photo" wherever they appear.
*/

$own = fn (string $title, string $file, string $used) => [
    'title' => $title,
    'author' => 'George Allinson (Transport) Ltd',
    'licence' => 'The firm\'s own photograph (rights to be confirmed)',
    'licence_url' => null,
    'source' => 'allinsontransport.co.uk',
    'url' => 'https://allinsontransport.co.uk/'.$file,
    'changes' => 'Converted to WebP; not enlarged.',
    'used_for' => $used,
];

return [
    'a40' => [
        'title' => 'George Allinson lorry 353 on the A40, Dixton, Monmouth',
        'author' => 'Jaggery (geograph.org.uk)',
        'licence' => 'CC BY-SA 2.0',
        'licence_url' => 'https://creativecommons.org/licenses/by-sa/2.0',
        'source' => 'Wikimedia Commons',
        'url' => 'https://commons.wikimedia.org/wiki/File:George_Allinson_lorry_353_on_the_A40,_Dixton,_Monmouth_-_geograph.org.uk_-_6793867.jpg',
        'changes' => 'Resized and converted to WebP.',
        'used_for' => 'Home page; About',
    ],
    'a1m' => [
        'title' => 'A1(M) Durham',
        'author' => 'Richard Cooke (geograph.org.uk)',
        'licence' => 'CC BY-SA 2.0',
        'licence_url' => 'https://creativecommons.org/licenses/by-sa/2.0',
        'source' => 'Wikimedia Commons',
        'url' => 'https://commons.wikimedia.org/wiki/File:A1(M)_Durham_-_geograph.org.uk_-_3219117.jpg',
        'changes' => 'Converted to WebP.',
        'used_for' => 'Services; quote request (illustrative)',
    ],
    'warehouse' => [
        'title' => 'Modern warehouse with pallet rack storage system',
        'author' => 'Axisadman',
        'licence' => 'CC BY-SA 3.0',
        'licence_url' => 'https://creativecommons.org/licenses/by-sa/3.0',
        'source' => 'Wikimedia Commons',
        'url' => 'https://commons.wikimedia.org/wiki/File:Modern_warehouse_with_pallet_rack_storage_system.jpg',
        'changes' => 'Resized and converted to WebP.',
        'used_for' => 'Fleet and warehousing; warehousing enquiry (illustrative)',
    ],
    'tacho' => [
        'title' => 'Volvo digital tachograph (cropped)',
        'author' => 'Elkawe',
        'licence' => 'Public domain (released by the author)',
        'licence_url' => null,
        'source' => 'Wikimedia Commons',
        'url' => 'https://commons.wikimedia.org/wiki/File:Volvo_Digital_tachograph_(cropped).jpg',
        'changes' => 'Resized and converted to WebP.',
        'used_for' => 'Careers (illustrative)',
    ],
    'own-depot-night' => $own('Faverdale depot at dusk', 'images/allinson_bg.jpg', 'Home (header); depots; contact'),
    'own-lineup' => $own('Scania tractor units lined up in Allinson livery', 'cmsimages/44136allinson.jpg', 'Fleet and warehousing; about'),
    'own-loading' => $own('Curtainsider loading by forklift', 'cmsimages/2962549625AT__1411.jpg', 'Home; services'),
    'own-loading-2' => $own('Loading a curtainsider at the container yard', 'cmsimages/38907AT__142.jpg', 'Careers'),
    'own-hiab' => $own('Hiab operator with a lorry-mounted crane', 'cmsimages/33322AT__193.jpg', 'Services; careers'),
    'own-reefer' => $own('Allinson tractor units with a refrigerated trailer', 'cmsimages/57608AT__138.jpg', 'Services'),
    'own-tipper' => $own('Allinson bulk trailer and drawbar', 'cmsimages/60482AT__189.jpg', 'Services'),
    'own-container' => $own('Container stacker loading a skeletal trailer', 'cmsimages/61354AT__119.jpg', 'Services; fleet and warehousing'),
    'own-warehouse' => $own('Bulk bags in the warehouse', 'cmsimages/83422AT__179.jpg', 'Services; warehousing'),
    'own-forklift' => $own('Forklift on the loading ramp', 'cmsimages/90149AT__109.jpg', 'Fleet and warehousing; careers'),
    'own-coils' => $own('Coiled pipe on a flatbed trailer', 'cmsimages/99708AT__129.jpg', 'Services'),
    'own-warehouse-hall' => $own('Warehouse hall', 'cmsimages/80356AT__155.jpg', 'Fleet and warehousing'),
];

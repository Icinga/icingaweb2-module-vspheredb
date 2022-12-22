<?php

return [
    'Dell Inc.' => [
        'PowerEdge R610' => 'https://i.dell.com/is/image/DellContent/content/dam/'
            . 'global-site-design/product_images/dell_enterprise_products/enterprise_systems/poweredge/'
            . 'poweredge_r610/front_facing/server-poweredge-r610-front-overhead-relativesized-500.jpg',
            // Alpha looks better, but has the wrong aspect ratio:
            // . 'poweredge_r610/best_of/server-poweredge-r610-bestof-500.png?fmt=png-alpha',
        'PowerEdge R620' => 'https://i.dell.com/is/image/DellContent/content/dam/'
            . 'global-site-design/product_images/dell_enterprise_products/enterprise_systems/poweredge/'
            . 'poweredge_r620/best_of/server-poweredge-r620-left-bestof-500.psd?fmt=png-alpha',
        'PowerEdge R720' => 'https://i.dell.com/is/image/DellContent/content/dam/'
            . 'global-site-design/product_images/dell_enterprise_products/enterprise_systems/poweredge/'
            . 'poweredge_r720/best_of/server-poweredge-r720-right-bestof-500.psd?fmt=png-alpha',

        'PowerEdge R730' => 'https://i.dell.com/is/image/DellContent/content/dam/'
            . 'global-site-design/product_images/dell_enterprise_products/enterprise_systems/poweredge/'
            . 'poweredge_r730/global_spi/server-poweredge-r730-left-bestof-500-ng-v2.psd?fmt=png-alpha',
        'PowerEdge R930' => 'https://i.dell.com/is/image/DellContent/content/dam/'
            . 'global-site-design/product_images/dell_enterprise_products/enterprise_systems/poweredge/'
            . 'poweredge_r930/global_spi/server-poweredge-r930-left-bestof-500-ng.psd?fmt=png-alpha',

        'PowerEdge R740' => 'https://i.dell.com/is/image/DellContent/content/dam/'
            . 'images/products/servers/poweredge/r740/'
            . 'dellemc-per740-24x25-bezel-lcd-2-above-ff-bold-reflection.psd?fmt=png-alpha',
        'PowerEdge R740xd' => 'https://i.dell.com/is/image/DellContent//content/dam/'
            . 'images/products/servers/poweredge/r740xd/dellemc-per740xd-24x25-bezel-2-lf.psd'
            . '?fmt=pjpg&pscan=auto&scl=1&hei=402&wid=1236&qlt=100,1&resMode=sharp2&size=1236,402&chrss=full',
        'PowerEdge R940' => 'https://i.dell.com/is/image/DellContent/content/dam/'
            . 'images/products/servers/poweredge/r940/dellemc-per940-24x2-5-bezel-lcd-lf.psd'
            . '?fmt=png-alpha&pscan=auto&scl=1&hei=402&wid=993&qlt=100,1&resMode=sharp2&size=993,402&chrss=full',
        'PowerEdge R750' => 'https://i.dell.com/is/image/DellContent/content/dam/'
            . 'global-site-design/product_images/dell_enterprise_products/enterprise_systems/poweredge/'
            . 'r_series/r750/global_spi/ng/enterprise-servers-poweredge-r750-lf-bestof-500-ng.psd?fmt=png-alpha',
        'PowerEdge R6515' => 'https://i.dell.com/is/image/DellContent/content/dam/'
            . 'images/products/servers/poweredge/r6515/dellemc-per6515-10x25-emc-lcd-bezel-lf.psd'
            . '?fmt=pjpg&pscan=auto&scl=1&hei=402&wid=2117&qlt=100,1&resMode=sharp2&size=2117,402&chrss=full',
        'PowerEdge R7515' => 'https://i.dell.com/is/image/DellContent/content/dam/'
            . 'global-site-design/product_images/dell_enterprise_products/enterprise_systems/poweredge/'
            . 'c6525/global_spi/ng/enterprise-server-poweredge-r7515-lf-bestof-500-ng.psd?fmt=png-alpha',
        'PowerEdge R7525' => 'https://i.dell.com/is/image/DellContent/content/dam/'
            . 'global-site-design/product_images/dell_enterprise_products/enterprise_systems/poweredge/'
            . 'poweredge_r7525/global_spi/ng/enterprise-servers-poweredge-r7525-lf-bestof-500-ng.psd?fmt=png-alpha',
    ],
    'HPE' => [
        // End of life, found no HPE URL:
        'ProLiant BL460c Gen10' => 'https://serverhero.de/media/image/category/1980/lg/'
            . 'hpe-proliant-bl460c-g10-hpe.jpg',

        // Instead of $zoom$, there is also: $superzoom$, $thumbnail$
        'ProLiant DL160 Gen10'              => 'https://assets.ext.hpe.com/is/image/hpedam/s00001901?$zoom$#.png',
        'ProLiant DL360 Gen10'              => 'https://assets.ext.hpe.com/is/image/hpedam/s00005869?$zoom$#.png',
        'ProLiant DL380 Gen10'              => [
            'url' => 'https://assets.ext.hpe.com/is/image/hpedam/s00009709?$zoom$#.png',
            'css' => 'clip-path: inset(34% 0 30% 0); margin: -18% -0 -12% -0;',
        ],
        'ProLiant DL380 Gen10 Plus'         => [
            'url' => 'https://assets.ext.hpe.com/is/image/hpedam/s00009868?$zoom$#.png',
        ],
        'ProLiant DL385 Gen10 Plus'         => 'https://assets.ext.hpe.com/is/image/hpedam/s00009923?$zoom$#.png',
        'ProLiant DL560 Gen10'              => [
            'url' => 'https://assets.ext.hpe.com/is/image/hpedam/s00002844?$zoom$#.png',
            'css' => 'clip-path: inset(44% 0 30% 0); margin: -30% -0 -20% -0;',
        ],
        'ProLiant DL580 Gen10'              => [
            'url' => 'https://assets.ext.hpe.com/is/image/hpedam/s00005353?$zoom$#.png',
            'css' => 'clip-path: inset(24% 0 20% 0); margin: -20% -0 -15% -0;',
        ],

        'Synergy 480 Gen10'                 => 'https://assets.ext.hpe.com/is/image/hpedam/s00002866?$zoom$#.png',
        'Synergy 480 Gen10 w/ PCIe Exp Mod' => 'https://assets.ext.hpe.com/is/image/hpedam/s00003516?$zoom$#.png',
        'Synergy 660 Gen10'                 => 'https://assets.ext.hpe.com/is/image/hpedam/s00005525?$zoom$#.png'

        // With cover:
        // 'ProLiant DL360 Gen10'           => 'https://assets.ext.hpe.com/is/image/hpedam/s00001312?$zoom$#.png',
        // 'ProLiant DL380 Gen10'           => 'https://assets.ext.hpe.com/is/image/hpedam/s00006498?$zoom$#.png',
        // 'ProLiant DL380 Gen10 Plus'      => 'https://assets.ext.hpe.com/is/image/hpedam/s00009867?$zoom$#.png',
        // 'ProLiant DL385 Gen10 Plus'      => 'https://assets.ext.hpe.com/is/image/hpedam/s00006463?$zoom$#.png',
        // 'ProLiant DL580 Gen10'           => 'https://assets.ext.hpe.com/is/image/hpedam/s00001910?$zoom$#.png',

        // Variant:
        // 'ProLiant DL560 Gen10' => 'https://assets.ext.hpe.com/is/image/hpedam/s00004976?$zoom$#.png',
    ],
    'HP' => [
        'ProLiant DL580 Gen9' => 'https://support.hpe.com/hpesc/public/api/document/c04683220/'
            . 'GUID-D1718179-3841-49FA-83BC-D24B8C5933FF-high.gif',
        'ProLiant DL360 Gen9' => 'https://techlibrary.hpe.com/docs/enterprise/servers/DL360Gen9/'
            . 'DL360Gen9-setup/system_setup_overview/222491.png',
        'ProLiant DL380 Gen9' => [
            'url' => 'https://techlibrary.hpe.com/docs/enterprise/servers/DL380Gen9/'
            . 'DL380Gen9-setup/system_setup_overview/222457.png',
            'css' => 'clip-path: inset(20% 0 15% 0); margin: -10% -0 -7% -0;',
        ],
        'ProLiant BL460c Gen9' => 'https://techlibrary.hpe.com/docs/enterprise/servers/BL460cGen9/'
            . 'BL460cGen9-setup/de/system_setup_overview/189999.png'
    ],
    'Lenovo' => [
        'ThinkAgile VX 1U Node -[7Y93CTO4WW]-' => 'https://lenovopress.com/assets/images/LP0932/VX-1U-overview.png',
        'ThinkSystem SR850 -[7X19CTO1WW]-' => 'https://lenovopress.com/assets/images/LP1301/'
            . 'ThinkSystem-SR850-V2-500px.png',
        'ThinkSystem SR650 -[7X06CTO1WW]-' => 'https://lenovopress.lenovo.com/assets/images/LP1050/'
            . 'ThinkSystem%20SR650%20server.jpg',
        'ThinkSystem SR950 -[7X12CTO1WW]-' => 'https://lenovopress.lenovo.com/assets/images/LP0647/'
            . 'ThinkSystem%20SR950.png',
        'ThinkAgile HX1321 Node -[7Y89CTO1WW]-' => 'https://lenovopress.lenovo.com/assets/images/LP0887/'
            . 'HX1321-overview.png',
        'ThinkAgile HX7520 Appliance -[7X84CTO6WW]-' => 'https://lenovopress.lenovo.com/assets/images/LP0730/'
            . 'HX7520-overview.png',
        'Lenovo ThinkAgile HX7520 Appliance -[7X84CTO6WW]-' => 'https://lenovopress.lenovo.com/assets/images/LP0730/'
            . 'HX7520-overview.png',
    ],
    'FUJITSU' => [
        'PRIMERGY CX2560 M5' => 'https://www.fujitsu.com/de/imagesgig5/W-DK43300_tcm20-4285182_tcm20-5309118-32.png',
        'PRIMERGY RX2540 M1' => 'https://www.fujitsu.com/global/imagesgig5/W-DK72231_tcm100-5877559_tcm100-5309118-32'
            . '.png',
        'PRIMERGY RX2540 M2' => 'https://www.fujitsu.com/de/Images/W-DK42852_tcm20-3057159.png',
        'PRIMERGY RX2540 M3' => 'https://www.fujitsu.com/de/Images/W-DK42852_tcm20-3057159.png',
        'PRIMERGY RX2540 M4' => 'https://www.fujitsu.com/de/Images/W-DK42852_tcm20-3057159.png',
    ],
    'IBM' => [
        '/^(?:System )?x3850 X6/' => 'https://lenovopress.com/assets/images/tips1084/0.212C.jpg',
        'IBM System x3650 M4: -[7915B3G]-' => 'https://www.ibm.com/common/ssi/GIF/ALET/JX3650M4.JPG',
        // Variants:
        //    System x3850 X6 -[3837CT0]-
        //    x3850 X6 -[3837ZMQ]-
        //    x3850 X6 -[3837Z91]-
        //    x3850 X6 -[3837Z91]-
        // Lenovo: https://lenovopress.com/assets/images/tips1250/Lenovo%20System%20x3850%20X6%20v4.jpg
    ],
    'Nutanix' => [
        'NX-3170-G8' => 'https://download.nutanix.com/documentation/NX-hardware/images/'
        . 'front-panel-callouts-nx3170g8-nx8170g8.png',
    ],
];

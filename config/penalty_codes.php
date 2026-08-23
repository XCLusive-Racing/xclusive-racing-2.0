<?php

return [
    'NONE'    => ['value' => 0,    'sr' => 0.00, 'label' => 'None / Requirements not met'],
    'INVALID' => ['value' => 0,    'sr' => 0.00, 'label' => 'Invalid Report'],
    'PENDING' => ['value' => 0,    'sr' => 0.00, 'label' => 'Pending Review'],
    'RI'      => ['value' => 0,    'sr' => 0.00, 'label' => 'Racing Incident'],
    'UI'      => ['value' => 0.2,  'sr' => 0.20, 'label' => 'Unnecessary Impeding'],
    'WEV'     => ['value' => 0.25, 'sr' => 0.40, 'label' => 'Weaving'],
    'MUB'     => ['value' => 0.3,  'sr' => 0.60, 'label' => 'Moving Under Braking'],
    'IBF'     => ['value' => 0.4,  'sr' => 0.80, 'label' => 'Ignoring Blue Flags'],
    'ULC'     => ['value' => 0.8,  'sr' => 1.00, 'label' => 'Unsportsmanlike Conduct'],
    'DR'      => ['value' => 0.35, 'sr' => 1.00, 'label' => 'Dangerous Rejoin'],
    'CT'      => ['value' => 0.45, 'sr' => 1.20, 'label' => 'Contact'],
    'DD'      => ['value' => 0.5,  'sr' => 1.40, 'label' => 'Dangerous Driving'],
    'FDOT'    => ['value' => 0.55, 'sr' => 1.60, 'label' => 'Forcing a Driver Off Track'],
    'CASC'    => ['value' => 0.8,  'sr' => 1.80, 'label' => 'Causing a Small Collision'],
    'CAC'     => ['value' => 1,    'sr' => 2.00, 'label' => 'Causing a Collision'],
    'CAHC'    => ['value' => 1.3,  'sr' => 2.20, 'label' => 'Causing a Heavy Collision'],
    'CAIC'    => ['value' => 4,    'sr' => 5.00, 'label' => 'Causing an Intentional Collision'],
];

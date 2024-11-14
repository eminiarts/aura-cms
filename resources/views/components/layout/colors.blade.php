@php
    use Aura\Base\TransformColor;

    // Assuming $settings is already defined
    $colorShades = ['25', '50', '100', '200', '300', '400', '500', '600', '700', '800', '900', '950'];
    $grayShades = ['25', '50', '100', '200', '300', '400', '500', '600', '700', '800', '900'];

    $defaultVariables = [
        '--sidebar-bg' => 'var(--primary-600)',
        '--sidebar-bg-hover' => 'var(--primary-500)',
        '--sidebar-bg-dropdown' => 'var(--primary-700)',
        '--sidebar-text' => 'var(--primary-400)',
        '--sidebar-icon' => 'var(--primary-300)',
        '--sidebar-icon-hover' => 'var(--primary-200)',
    ];

    $colorPalettes = [
        'aura' => [
            'colors' => [
                'primary-25' => TransformColor::hexToRgb('#fbfeff'),
                'primary-50' => TransformColor::hexToRgb('#E9EEFD'),
                'primary-100' => TransformColor::hexToRgb('#C9D9FB'),
                'primary-200' => TransformColor::hexToRgb('#A3BFF9'),
                'primary-300' => TransformColor::hexToRgb('#699EF6'),
                'primary-400' => TransformColor::hexToRgb('#3C7EF4'),
                'primary-500' => TransformColor::hexToRgb('#153AEF'),
                'primary-600' => TransformColor::hexToRgb('#0E30D7'),
                'primary-700' => TransformColor::hexToRgb('#0A25B9'),
                'primary-800' => TransformColor::hexToRgb('#071B9B'),
                'primary-900' => TransformColor::hexToRgb('#04127C'),
                'primary-950' => TransformColor::hexToRgb('#010850'),
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-700)',
                '--sidebar-bg-hover' => 'var(--primary-600)',
                '--sidebar-bg-dropdown' => 'var(--primary-800)',
                '--sidebar-text' => 'var(--primary-400)',
                '--sidebar-icon' => 'var(--primary-300)',
                '--sidebar-icon-hover' => 'var(--primary-200)',
            ],
        ],
        'red' => [
            'colors' => [
                'primary-25' => '254 242 242',
                'primary-50' => '254 226 226',
                'primary-100' => '252 202 202',
                'primary-200' => '252 202 202',
                'primary-300' => '252 165 165',
                'primary-400' => '248 113 113',
                'primary-500' => '239 68 68',
                'primary-600' => '220 38 38',
                'primary-700' => '185 28 28',
                'primary-800' => '153 27 27',
                'primary-900' => '127 29 29',
            ]
        ],
        'orange' => [
            'colors' => [
                'primary-25' => '255 247 237',
                'primary-50' => '255 237 213',
                'primary-100' => '254 215 170',
                'primary-200' => '253 186 116',
                'primary-300' => '251 146 60',
                'primary-400' => '249 115 22',
                'primary-500' => '234 88 12',
                'primary-600' => '194 65 12',
                'primary-700' => '154 52 18',
                'primary-800' => '124 45 18',
                'primary-900' => '101 43 18',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-500)',
                '--sidebar-bg-hover' => 'var(--primary-600)',
                '--sidebar-bg-dropdown' => 'var(--primary-600)',
                '--sidebar-text' => 'var(--primary-200)',
                '--sidebar-icon' => 'var(--primary-200)',
                '--sidebar-icon-hover' => 'var(--primary-100)',
            ],
        ],
        // Continue with other color palettes...
        'amber' => [
            'colors' => [
                'primary-25' => '255 251 235',
                'primary-50' => '254 243 199',
                'primary-100' => '253 230 138',
                'primary-200' => '252 205 77',
                'primary-300' => '251 191 36',
                'primary-400' => '245 158 11',
                'primary-500' => '217 119 6',
                'primary-600' => '180 83 9',
                'primary-700' => '146 64 14',
                'primary-800' => '120 53 15',
                'primary-900' => '99 49 18',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-500)',
                '--sidebar-bg-hover' => 'var(--primary-600)',
                '--sidebar-bg-dropdown' => 'var(--primary-600)',
                '--sidebar-text' => 'var(--primary-200)',
                '--sidebar-icon' => 'var(--primary-200)',
                '--sidebar-icon-hover' => 'var(--primary-100)',
            ],
        ],
        'yellow' => [
            'colors' => [
                'primary-25' => '255 254 232',
                'primary-50' => '254 249 195',
                'primary-100' => '254 240 138',
                'primary-200' => '253 208 71',
                'primary-300' => '250 204 21',
                'primary-400' => '234 176 8',
                'primary-500' => '202 138 4',
                'primary-600' => '161 98 7',
                'primary-700' => '133 77 14',
                'primary-800' => '113 63 18',
                'primary-900' => '101 54 16',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-500)',
                '--sidebar-bg-hover' => 'var(--primary-600)',
                '--sidebar-bg-dropdown' => 'var(--primary-600)',
                '--sidebar-text' => 'var(--primary-200)',
                '--sidebar-icon' => 'var(--primary-200)',
                '--sidebar-icon-hover' => 'var(--primary-100)',
            ],
        ],
        'lime' => [
            'colors' => [
                'primary-25' => '247 254 231',
                'primary-50' => '236 252 203',
                'primary-100' => '217 249 157',
                'primary-200' => '190 242 100',
                'primary-300' => '163 230 53',
                'primary-400' => '132 204 22',
                'primary-500' => '101 163 13',
                'primary-600' => '77 124 15',
                'primary-700' => '63 98 18',
                'primary-800' => '54 83 20',
                'primary-900' => '47 75 18',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-700)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-400)',
                '--sidebar-icon' => 'var(--primary-300)',
                '--sidebar-icon-hover' => 'var(--primary-200)',
            ],
        ],
        'green' => [
            'colors' => [
                'primary-25' => '240 253 244',
                'primary-50' => '220 252 231',
                'primary-100' => '187 247 208',
                'primary-200' => '134 239 172',
                'primary-300' => '74 222 128',
                'primary-400' => '34 197 94',
                'primary-500' => '22 163 74',
                'primary-600' => '21 128 61',
                'primary-700' => '22 101 52',
                'primary-800' => '20 83 45',
                'primary-900' => '20 78 38',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-400)',
            ],
        ],
        'emerald' => [
            'colors' => [
                'primary-25' => '236 253 245',
                'primary-50' => '209 250 229',
                'primary-100' => '167 243 208',
                'primary-200' => '110 231 183',
                'primary-300' => '52 211 153',
                'primary-400' => '16 185 129',
                'primary-500' => '5 150 105',
                'primary-600' => '4 120 87',
                'primary-700' => '6 95 70',
                'primary-800' => '6 78 59',
                'primary-900' => '6 78 59',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-700)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-400)',
                '--sidebar-icon' => 'var(--primary-300)',
                '--sidebar-icon-hover' => 'var(--primary-200)',
            ],
        ],
        'teal' => [
            'colors' => [
                'primary-25' => '240 240 250',
                'primary-50' => '204 251 241',
                'primary-100' => '153 246 228',
                'primary-200' => '94 234 212',
                'primary-300' => '45 212 191',
                'primary-400' => '20 184 166',
                'primary-500' => '13 148 136',
                'primary-600' => '15 118 110',
                'primary-700' => '17 94 89',
                'primary-800' => '20 78 74',
                'primary-900' => '20 78 72',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-700)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-400)',
                '--sidebar-icon' => 'var(--primary-300)',
                '--sidebar-icon-hover' => 'var(--primary-200)',
            ],
        ],
        'cyan' => [
            'colors' => [
                'primary-25' => '236 254 255',
                'primary-50' => '207 250 254',
                'primary-100' => '165 243 252',
                'primary-200' => '103 232 249',
                'primary-300' => '34 211 238',
                'primary-400' => '6 182 212',
                'primary-500' => '8 145 178',
                'primary-600' => '14 116 144',
                'primary-700' => '21 94 117',
                'primary-800' => '22 78 99',
                'primary-900' => '26 54 93',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-500)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-300)',
                '--sidebar-icon' => 'var(--primary-200)',
                '--sidebar-icon-hover' => 'var(--primary-100)',
            ],
        ],
        'sky' => [
            'colors' => [
                'primary-25' => '240 249 255',
                'primary-50' => '224 242 254',
                'primary-100' => '186 230 253',
                'primary-200' => '125 211 252',
                'primary-300' => '56 189 248',
                'primary-400' => '14 165 233',
                'primary-500' => '2 132 199',
                'primary-600' => '3 105 161',
                'primary-700' => '7 89 133',
                'primary-800' => '12 74 110',
                'primary-900' => '12 74 110',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-500)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-300)',
                '--sidebar-icon' => 'var(--primary-200)',
                '--sidebar-icon-hover' => 'var(--primary-100)',
            ],
        ],
        'blue' => [
            'colors' => [
                'primary-25' => '240 255 255',
                'primary-50' => '219 190 254',
                'primary-100' => '191 219 254',
                'primary-200' => '147 197 253',
                'primary-300' => '96 165 250',
                'primary-400' => '59 130 246',
                'primary-500' => '37 99 235',
                'primary-600' => '29 78 216',
                'primary-700' => '30 64 175',
                'primary-800' => '30 58 138',
                'primary-900' => '30 54 104',
            ],
        ],
        'indigo' => [
            'colors' => [
                'primary-25' => '240 239 255',
                'primary-50' => '224 231 255',
                'primary-100' => '199 210 254',
                'primary-200' => '165 180 252',
                'primary-300' => '129 140 248',
                'primary-400' => '99 182 241',
                'primary-500' => '79 70 229',
                'primary-600' => '67 56 202',
                'primary-700' => '55 48 163',
                'primary-800' => '49 46 129',
                'primary-900' => '39 44 97',
            ],
        ],
        'violet' => [
            'colors' => [
                'primary-25' => '245 243 255',
                'primary-50' => '237 233 254',
                'primary-100' => '221 214 254',
                'primary-200' => '196 180 253',
                'primary-300' => '167 139 250',
                'primary-400' => '139 92 246',
                'primary-500' => '124 62 237',
                'primary-600' => '109 40 217',
                'primary-700' => '91 33 182',
                'primary-800' => '76 29 149',
                'primary-900' => '63 24 95',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-500)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-300)',
                '--sidebar-icon' => 'var(--primary-200)',
                '--sidebar-icon-hover' => 'var(--primary-100)',
            ],
        ],
        'purple' => [
            'colors' => [
                'primary-25' => '250 245 255',
                'primary-50' => '243 232 255',
                'primary-100' => '233 213 255',
                'primary-200' => '216 180 254',
                'primary-300' => '192 132 252',
                'primary-400' => '168 85 247',
                'primary-500' => '147 51 234',
                'primary-600' => '126 34 206',
                'primary-700' => '107 33 168',
                'primary-800' => '88 28 135',
                'primary-900' => '79 26 111',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-500)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-300)',
                '--sidebar-icon' => 'var(--primary-200)',
                '--sidebar-icon-hover' => 'var(--primary-100)',
            ],
        ],
        'fuchsia' => [
            'colors' => [
                'primary-25' => '250 244 255',
                'primary-50' => '250 232 255',
                'primary-100' => '245 208 254',
                'primary-200' => '240 171 252',
                'primary-300' => '232 121 249',
                'primary-400' => '217 70 239',
                'primary-500' => '192 38 211',
                'primary-600' => '162 28 175',
                'primary-700' => '134 25 143',
                'primary-800' => '112 26 117',
                'primary-900' => '99 25 99',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-500)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-300)',
                '--sidebar-icon' => 'var(--primary-200)',
                '--sidebar-icon-hover' => 'var(--primary-100)',
            ],
        ],
        'pink' => [
            'colors' => [
                'primary-25' => '255 242 248',
                'primary-50' => '252 231 243',
                'primary-100' => '251 207 232',
                'primary-200' => '249 168 212',
                'primary-300' => '244 114 182',
                'primary-400' => '236 72 153',
                'primary-500' => '219 39 119',
                'primary-600' => '190 24 93',
                'primary-700' => '157 24 77',
                'primary-800' => '131 24 67',
                'primary-900' => '107 26 61',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-500)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-300)',
                '--sidebar-icon' => 'var(--primary-200)',
                '--sidebar-icon-hover' => 'var(--primary-100)',
            ],
        ],
        'rose' => [
            'colors' => [
                'primary-25' => '255 241 242',
                'primary-50' => '254 228 230',
                'primary-100' => '252 205 211',
                'primary-200' => '249 164 175',
                'primary-300' => '251 113 131',
                'primary-400' => '244 63 94',
                'primary-500' => '225 29 72',
                'primary-600' => '190 18 60',
                'primary-700' => '159 18 57',
                'primary-800' => '136 19 55',
                'primary-900' => '119 30 61',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-500)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-300)',
                '--sidebar-icon' => 'var(--primary-200)',
                '--sidebar-icon-hover' => 'var(--primary-100)',
            ],
        ],
        'mountain-meadow' => [
            'colors' => [
                'primary-25' => '255 243 250',
                'primary-50' => '240 253 249',
                'primary-100' => '204 251 190',
                'primary-200' => '153 246 223',
                'primary-300' => '94 228 204',
                'primary-400' => '45 212 182',
                'primary-500' => '18 169 144',
                'primary-600' => '13 148 128',
                'primary-700' => '15 118 104',
                'primary-800' => '17 94 85',
                'primary-900' => '20 78 71',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-500)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-300)',
                '--sidebar-icon' => 'var(--primary-100)',
                '--sidebar-icon-hover' => 'var(--primary-50)',
            ],
        ],
        'sandal' => [
            'colors' => [
                'primary-25' => '250 250 250',
                'primary-50' => '248 248 252',
                'primary-100' => '240 238 228',
                'primary-200' => '223 218 201',
                'primary-300' => '202 193 171',
                'primary-400' => '181 164 131',
                'primary-500' => '169 150 112',
                'primary-600' => '151 127 94',
                'primary-700' => '127 104 79',
                'primary-800' => '104 84 64',
                'primary-900' => '85 66 51',
            ],
        ],
        'slate' => [
            'colors' => [
                'primary-25' => '250 250 255',
                'primary-50' => '249 250 251',
                'primary-100' => '241 245 249',
                'primary-200' => '226 232 240',
                'primary-300' => '203 213 225',
                'primary-400' => '148 163 184',
                'primary-500' => '100 116 139',
                'primary-600' => '71 85 105',
                'primary-700' => '52 65 85',
                'primary-800' => '30 41 59',
                'primary-900' => '15 23 42',
            ],
        ],
        'dark-slate' => [
            'colors' => [
                'primary-25' => '250 250 255',
                'primary-50' => '249 250 251',
                'primary-100' => '241 245 249',
                'primary-200' => '226 232 240',
                'primary-300' => '203 213 225',
                'primary-400' => '138 153 174',
                'primary-500' => '90 106 129',
                'primary-600' => '59 73 93',
                'primary-700' => '20 34 40',
                'primary-800' => '4 12 20',
                'primary-900' => '0 4 8',
            ],
        ],
        'purple-slate' => [
            'colors' => [
                'primary-25' => '255 250 255',
                'primary-50' => '248 246 254',
                'primary-100' => '243 241 249',
                'primary-200' => '230 226 240',
                'primary-300' => '209 203 225',
                'primary-400' => '159 148 184',
                'primary-500' => '112 100 139',
                'primary-600' => '82 71 105',
                'primary-700' => '62 51 85',
                'primary-800' => '40 30 59',
                'primary-900' => '27 15 42',
            ],
        ],
        'blackout' => [
            'colors' => [
                'primary-25' => '250 250 250',
                'primary-50' => '249 250 251',
                'primary-100' => '243 244 246',
                'primary-200' => '229 231 235',
                'primary-300' => '209 213 219',
                'primary-400' => '156 163 175',
                'primary-500' => '75 85 99',
                'primary-600' => '55 65 81',
                'primary-700' => '17 24 39',
                'primary-800' => '0 0 0',
                'primary-900' => '0 0 0',
            ],
        ],
        'gray' => [
            'colors' => [
                'primary-25' => '250 250 250',
                'primary-50' => '249 250 251',
                'primary-100' => '243 244 246',
                'primary-200' => '229 231 235',
                'primary-300' => '209 213 219',
                'primary-400' => '156 163 175',
                'primary-500' => '107 114 128',
                'primary-600' => '75 85 99',
                'primary-700' => '55 65 81',
                'primary-800' => '31 41 55',
                'primary-900' => '17 24 39',
            ],
        ],
        'zinc' => [
            'colors' => [
                'primary-25' => '249 250 251',
                'primary-50' => '250 250 250',
                'primary-100' => '244 244 245',
                'primary-200' => '228 228 231',
                'primary-300' => '212 212 216',
                'primary-400' => '161 161 170',
                'primary-500' => '113 113 122',
                'primary-600' => '82 82 91',
                'primary-700' => '63 63 70',
                'primary-800' => '39 39 42',
                'primary-900' => '24 24 27',
            ],
        ],
        'neutral' => [
            'colors' => [
                'primary-25' => '253 253 253',
                'primary-50' => '250 250 250',
                'primary-100' => '245 245 245',
                'primary-200' => '229 229 229',
                'primary-300' => '212 212 212',
                'primary-400' => '163 163 163',
                'primary-500' => '115 115 115',
                'primary-600' => '82 82 82',
                'primary-700' => '64 64 64',
                'primary-800' => '38 38 38',
                'primary-900' => '23 23 23',
            ],
        ],
        'stone' => [
            'colors' => [
                'primary-25' => '253 253 250',
                'primary-50' => '250 250 249',
                'primary-100' => '245 245 244',
                'primary-200' => '231 229 228',
                'primary-300' => '214 211 209',
                'primary-400' => '168 162 158',
                'primary-500' => '120 113 108',
                'primary-600' => '87 83 78',
                'primary-700' => '68 64 60',
                'primary-800' => '41 37 36',
                'primary-900' => '28 25 23',
            ],
        ],
        'blue' => [
            'colors' => [
                'primary-25' => '250 250 255',
                'primary-50' => '249 250 251',
                'primary-100' => '241 245 249',
                'primary-200' => '226 232 240',
                'primary-300' => '203 213 225',
                'primary-400' => '148 163 184',
                'primary-500' => '100 116 139',
                'primary-600' => '71 85 105',
                'primary-700' => '52 65 85',
                'primary-800' => '30 41 59',
                'primary-900' => '15 23 42',
            ],
        ],
        'smaragd' => [
            'colors' => [
                'primary-25' => '252 255 253',
                'primary-50' => '242 247 243',
                'primary-100' => '223 236 225',
                'primary-200' => '194 214 198',
                'primary-300' => '158 183 166',
                'primary-400' => '134 156 141',
                'primary-500' => '86 113 95',
                'primary-600' => '66 87 74',
                'primary-700' => '54 68 60',
                'primary-800' => '47 55 50',
                'primary-900' => '40 42 41',
            ],
        ],
    ];

    $grayColorPalettes = [
        'slate' => [
            'colors' => [
                'gray-25' => '250 250 255',
                'gray-50' => '249 250 251',
                'gray-100' => '241 245 249',
                'gray-200' => '226 232 240',
                'gray-300' => '203 213 225',
                'gray-400' => '148 163 184',
                'gray-500' => '100 116 139',
                'gray-600' => '71 85 105',
                'gray-700' => '52 65 85',
                'gray-800' => '30 41 59',
                'gray-900' => '15 23 42',
            ],
        ],
        'dark-slate' => [
            'colors' => [
                'gray-25' => '250 250 255',
                'gray-50' => '249 250 251',
                'gray-100' => '241 245 249',
                'gray-200' => '226 232 240',
                'gray-300' => '203 213 225',
                'gray-400' => '138 153 174',
                'gray-500' => '90 106 129',
                'gray-600' => '59 73 93',
                'gray-700' => '20 34 40',
                'gray-800' => '4 12 20',
                'gray-900' => '0 4 8',
            ],
        ],
        'purple-slate' => [
            'colors' => [
                'gray-25' => '255 250 255',
                'gray-50' => '248 246 254',
                'gray-100' => '243 241 249',
                'gray-200' => '230 226 240',
                'gray-300' => '209 203 225',
                'gray-400' => '159 148 184',
                'gray-500' => '112 100 139',
                'gray-600' => '82 71 105',
                'gray-700' => '62 51 85',
                'gray-800' => '40 30 59',
                'gray-900' => '27 15 42',
            ],
        ],
        'blackout' => [
            'colors' => [
                'gray-25' => '250 250 250',
                'gray-50' => '249 250 251',
                'gray-100' => '243 244 246',
                'gray-200' => '229 231 235',
                'gray-300' => '209 213 219',
                'gray-400' => '156 163 175',
                'gray-500' => '75 85 99',
                'gray-600' => '55 65 81',
                'gray-700' => '17 24 39',
                'gray-800' => '0 0 0',
                'gray-900' => '0 0 0',
            ],
        ],
        'gray' => [
            'colors' => [
                'gray-25' => '250 250 250',
                'gray-50' => '249 250 251',
                'gray-100' => '243 244 246',
                'gray-200' => '229 231 235',
                'gray-300' => '209 213 219',
                'gray-400' => '156 163 175',
                'gray-500' => '107 114 128',
                'gray-600' => '75 85 99',
                'gray-700' => '55 65 81',
                'gray-800' => '31 41 55',
                'gray-900' => '17 24 39',
            ],
        ],
        'zinc' => [
            'colors' => [
                'gray-25' => '249 250 251',
                'gray-50' => '250 250 250',
                'gray-100' => '244 244 245',
                'gray-200' => '228 228 231',
                'gray-300' => '212 212 216',
                'gray-400' => '161 161 170',
                'gray-500' => '113 113 122',
                'gray-600' => '82 82 91',
                'gray-700' => '63 63 70',
                'gray-800' => '39 39 42',
                'gray-900' => '24 24 27',
            ],
        ],
        'neutral' => [
            'colors' => [
                'gray-25' => '253 253 253',
                'gray-50' => '250 250 250',
                'gray-100' => '245 245 245',
                'gray-200' => '229 229 229',
                'gray-300' => '212 212 212',
                'gray-400' => '163 163 163',
                'gray-500' => '115 115 115',
                'gray-600' => '82 82 82',
                'gray-700' => '64 64 64',
                'gray-800' => '38 38 38',
                'gray-900' => '23 23 23',
            ],
        ],
        'stone' => [
            'colors' => [
                'gray-25' => '253 253 250',
                'gray-50' => '250 250 249',
                'gray-100' => '245 245 244',
                'gray-200' => '231 229 228',
                'gray-300' => '214 211 209',
                'gray-400' => '168 162 158',
                'gray-500' => '120 113 108',
                'gray-600' => '87 83 78',
                'gray-700' => '68 64 60',
                'gray-800' => '41 37 36',
                'gray-900' => '28 25 23',
            ],
        ],
        'blue' => [
            'colors' => [
                'gray-25' => '250 250 255',
                'gray-50' => '249 250 251',
                'gray-100' => '241 245 249',
                'gray-200' => '226 232 240',
                'gray-300' => '203 213 225',
                'gray-400' => '148 163 184',
                'gray-500' => '100 116 139',
                'gray-600' => '71 85 105',
                'gray-700' => '52 65 85',
                'gray-800' => '30 41 59',
                'gray-900' => '15 23 42',
            ],
        ],
        'smaragd' => [
            'colors' => [
                'gray-25' => '252 255 253',
                'gray-50' => '242 247 243',
                'gray-100' => '223 236 225',
                'gray-200' => '194 214 198',
                'gray-300' => '158 183 166',
                'gray-400' => '134 156 141',
                'gray-500' => '86 113 95',
                'gray-600' => '66 87 74',
                'gray-700' => '54 68 60',
                'gray-800' => '47 55 50',
                'gray-900' => '40 42 41',
            ],
        ],
    ];

    $selectedPalette = $settings['color-palette'] ?? 'aura';
    $selectedGrayPalette = $settings['gray-color-palette'] ?? 'slate';

    if ($selectedPalette === 'custom') {
        $colors = [];
        foreach ($colorShades as $shade) {
            $key = 'primary-' . $shade;
            $colors[$key] = isset($settings[$key]) ? TransformColor::hexToRgb($settings[$key]) : 'default value';
        }
        $variables = $defaultVariables;
    } else {
        $palette = $colorPalettes[$selectedPalette] ?? $colorPalettes['aura'];
        $colors = $palette['colors'];
        $variables = array_merge($defaultVariables, $palette['variables'] ?? []);
    }

    if ($selectedGrayPalette === 'custom') {
        $grayColors = [];
        foreach ($grayShades as $shade) {
            $key = 'gray-' . $shade;
            $grayColors[$key] = isset($settings[$key]) ? TransformColor::hexToRgb($settings[$key]) : 'default value';
        }
    } else {
        $grayPalette = $grayColorPalettes[$selectedGrayPalette] ?? $grayColorPalettes['slate'];
        $grayColors = $grayPalette['colors'];
    }
@endphp

<style>
    :root {
        @foreach ($colors as $key => $value)
            --{{ $key }}: {{ $value }};
        @endforeach

        @foreach ($variables as $key => $value)
            {{ $key }}: {{ $value }};
        @endforeach
    }

    :root {
        @foreach ($grayColors as $key => $value)
            --{{ $key }}: {{ $value }};
        @endforeach
    }
</style>

<script>
    function getCssVariableValue(variableName) {
        var rgb = getComputedStyle(document.documentElement).getPropertyValue(variableName);
        rgb = rgb.trim().split(" ");
        return rgbToHex(parseInt(rgb[0]), parseInt(rgb[1]), parseInt(rgb[2]));
    }

    function rgbToHex(r, g, b) {
        return "#" + ((1 << 24) + (r << 16) + (g << 8) + (+b)).toString(16).slice(1);
    }

    @if(optional($settings)['darkmode-type'] == 'dark')
    document.documentElement.classList.add('dark')
    @elseif (optional($settings)['darkmode-type'] == 'light')
    document.documentElement.classList.remove('dark')
    document.documentElement.classList.add('light')
    @else
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.documentElement.classList.add('dark')
    }
    @endif
</script>

<?php

namespace HSDCL\DteCl\Tests\Feature;

use HSDCL\DteCl\Sii\Base\Dte;
use HSDCL\DteCl\Sii\Certification\PacketDteBuilder;
use HSDCL\DteCl\Sii\Certification\ExemptCertificationBuilder;
use HSDCL\DteCl\Sii\Certification\FileSource;
use HSDCL\DteCl\Sii\Certification\BasicCertificationBuilder;
use HSDCL\DteCl\Sii\Certification\JsonSource;
use HSDCL\DteCl\Sii\Certification\SimulationBuilder;
use HSDCL\DteCl\Util\Configuration;
use HSDCL\DteCl\Tests\TestCase;
use Illuminate\Support\Str;
use sasco\LibreDTE\FirmaElectronica;
use sasco\LibreDTE\Sii\Folios;

/**
 * Class ExampleTest
 * @package HSDCL\DteCl\Tests
 * @author David Lopez <dleo.lopez@gmail.com>
 */
class SimulationBuilderTest extends TestCase
{
    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var FirmaElectronica
     */
    protected $firma;

    /**
     * @var Folios
     */
    protected $folios;

    /**
     * @var BasicCertificationBuilder
     */
    protected $certification;

    /**
     * @var array
     */
    protected $caratula;

    /**
     * @var array
     */
    protected $startFolios;

    /**
     * @author David Lopez <dlopez@hsd.cl>
     */
    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
        $dotenv->load();
        $emisor = [
            'RUTEmisor'  => env('RUTEmisor'),
            'RznSoc'     => env('RznSoc'),
            'GiroEmis'   => env('GiroEmis'),
            'Acteco'     => env('Acteco'),
            'DirOrigen'  => env('DirOrigen'),
            'CmnaOrigen' => env('CmnaOrigen'),
        ];
        $receptor = [
            'RUTRecep'    => env('RUTRecep'),    #'81515100-3',
            'RznSocRecep' => env('RznSocRecep'), #'SELIM DABED SPA.',
            'GiroRecep'   => env('GiroRecep'),   #'BARRACA Y FERRETERIA',
            'DirRecep'    => env('DirRecep'),    #'BENAVENTE 516',
            'CmnaRecep'   => env('CmnaRecep')    #'OVALLE',
        ];
        $this->caratula = [
            'RutEnvia'    => env('RutEnvia'),    #'12021283-4',
            'RutReceptor' => env('RutReceptor'), #'60803000-K',
            'FchResol'    => env('FechaResolucion'),
            'NroResol'    => 0,
        ];
        # Folios Begin
        $this->startFolios = [
            Dte::FACTURA_ELECTRONICA         => '112',
            Dte::NOTA_DE_CREDITO_ELECTRONICA => '87', # 118
            Dte::NOTA_DE_DEBITO_ELECTRONICA  => '64', # 71
            Dte::FACTURA_EXENTA_ELECTRONICA  => '60'  # 70
        ];
        $documentos = [
            // 1 - Factura: 1 producto, sin descuentos
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 33,
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Palta Hass',
                        'QtyItem' => 34428,
                        'PrcItem' => 77,
                    ],
                ],
            ],
            // 2 - Factura: 3 productos, sin descuentos
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 33,
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Crimson Seedless 5 kg',
                        'QtyItem' => 150079,
                        'PrcItem' => 110,
                    ],
                    [
                        'NmbItem' => 'Thompson Seedless 5 kg',
                        'QtyItem' => 100000,
                        'PrcItem' => 110,
                    ]
                ],
            ],
            // 3 - Factura: 2 productos iguales, sin descuentos
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 33,
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Palta Hass',
                        'QtyItem' => 2,
                        'PrcItem' => 35000,
                    ],
                ],
            ],
            // 4 - Factura: 2 productos iguales, 10% de descuento c/u
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 33,
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Palta Hass',
                        'QtyItem' => 2,
                        'PrcItem' => 35000,
                        'DescuentoPct' => 10,
                    ],
                ],
            ],
            // 5 - Factura: 2 productos iguales, 6% de descuento global
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 33,
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Palta Hass',
                        'QtyItem' => 2,
                        'PrcItem' => 35000,
                    ],
                ],
                'DscRcgGlobal' => [
                    'TpoMov' => 'D',
                    'TpoValor' => '%',
                    'ValorDR' => 6,
                ]
            ],
            // 6 - Factura: 2 productos iguales, 5.000 de descuento global
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 33,
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Palta Hass',
                        'QtyItem' => 2,
                        'PrcItem' => 35000,
                    ],
                ],
                'DscRcgGlobal' => [
                    'TpoMov' => 'D',
                    'TpoValor' => '$',
                    'ValorDR' => 5000,
                ]
            ],
            // 7 - Factura: 10 productos iguales, c/u 10% descuento, 5.000 descuento global
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 33,
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Palta Hass',
                        'QtyItem' => 10,
                        'PrcItem' => 35000,
                        'DescuentoPct' => 10,
                    ],
                ],
                'DscRcgGlobal' => [
                    'TpoMov' => 'D',
                    'TpoValor' => '$',
                    'ValorDR' => 5000,
                ]
            ],
            // 8 - Factura: 10 productos iguales, c/u 10% descuento, 7% descuento global
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 33,
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Palta Hass',
                        'QtyItem' => 10,
                        'PrcItem' => 35000,
                        'DescuentoPct' => 10,
                    ],
                ],
                'DscRcgGlobal' => [
                    'TpoMov' => 'D',
                    'TpoValor' => '%',
                    'ValorDR' => 7,
                ]
            ],
            // 9 - Factura: 3 productos, 6% descuento global
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 33,
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Palta Hass',
                        'QtyItem' => 1,
                        'PrcItem' => 35000,
                    ],
                    [
                        'NmbItem' => 'Crimson Seedless 5 kg',
                        'QtyItem' => 1,
                        'PrcItem' => 10000,
                    ],
                    [
                        'NmbItem' => 'Thompson Seedless 5 kg',
                        'QtyItem' => 1,
                        'PrcItem' => 25000,
                    ],
                ],
                'DscRcgGlobal' => [
                    'TpoMov' => 'D',
                    'TpoValor' => '%',
                    'ValorDR' => 6,
                ]
            ],
            // 10 - Factura: 3 productos, 6% descuento global, un producto con 50% descuento
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 33,
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Palta Hass',
                        'QtyItem' => 1,
                        'PrcItem' => 35000,
                    ],
                    [
                        'NmbItem' => 'Crimson Seedless 5 kg',
                        'QtyItem' => 1,
                        'PrcItem' => 10000,
                        'DescuentoPct' => 50,
                    ],
                    [
                        'NmbItem' => 'Thompson Seedless 5 kg',
                        'QtyItem' => 1,
                        'PrcItem' => 25000,
                    ],
                ],
                'DscRcgGlobal' => [
                    'TpoMov' => 'D',
                    'TpoValor' => '%',
                    'ValorDR' => 6,
                ]
            ],
            // 11 - Nota de crédito: corrige dirección del receptor
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 61,
                    ],
                    'Totales' => [
                        'MntTotal' => 0,
                    ]
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Palta Hass',
                    ],
                ],
                'Referencia' => [
                    'TpoDocRef' => 33,
                    'FolioRef'  => $this->startFolios[Dte::FACTURA_ELECTRONICA],
                    'CodRef'    => 2,
                    'RazonRef'  => 'Corrige dirección del receptor',
                ]
            ],
            // 12 - Nota de crédito: anula factura
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 61,
                    ],
                    'Totales' => [
                        // estos valores serán calculados automáticamente
                        'MntNeto' => 0,
                        'TasaIVA' => \sasco\LibreDTE\Sii::getIVA(),
                        'IVA' => 0,
                        'MntTotal' => 0,
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Palta Hass',
                        'QtyItem' => 2,
                        'PrcItem' => 35000,
                        'DescuentoPct' => 10,
                    ],
                ],
                'Referencia' => [
                    'TpoDocRef' => 33,
                    'FolioRef'  => $this->startFolios[Dte::FACTURA_ELECTRONICA] + 3,
                    'CodRef'    => 1,
                    'RazonRef'  => 'Anula factura',
                ]
            ],
            // 13 - Nota de crédito: devolución mercadería (1 producto)
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 61,
                    ],
                    'Totales' => [
                        // estos valores serán calculados automáticamente
                        'MntNeto' => 0,
                        'TasaIVA' => \sasco\LibreDTE\Sii::getIVA(),
                        'IVA' => 0,
                        'MntTotal' => 0,
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Palta Hass',
                        'QtyItem' => 1,
                        'PrcItem' => 35000,
                    ],
                ],
                'Referencia' => [
                    'TpoDocRef' => 33,
                    'FolioRef'  => $this->startFolios[Dte::FACTURA_ELECTRONICA] + 2,
                    'CodRef'    => 3,
                    'RazonRef'  => 'Devolución mercadería',
                ]
            ],
            // 14 - Nota de débito: anula nota de crédito de devolución de mercadería
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 56,
                    ],
                    'Totales' => [
                        // estos valores serán calculados automáticamente
                        'MntNeto' => 0,
                        'TasaIVA' => \sasco\LibreDTE\Sii::getIVA(),
                        'IVA' => 0,
                        'MntTotal' => 0,
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Palta Hass',
                        'QtyItem' => 1,
                        'PrcItem' => 35000,
                    ],
                ],
                'Referencia' => [
                    'TpoDocRef' => 61,
                    'FolioRef'  => $this->startFolios[Dte::NOTA_DE_CREDITO_ELECTRONICA] + 2,
                    'CodRef'    => 1,
                    'RazonRef'  => 'Anula nota de crédito electrónica',
                ]
            ],
            // 15 - Factura: 1 producto afecto y un servicio exento, sin descuentos
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 33,
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Punto de acceso WAP54G',
                        'QtyItem' => 1,
                        'PrcItem' => 35000,
                    ],
                    [
                        'IndExe' => 1,
                        'NmbItem' => 'Asesoría en instalación de AP',
                        'QtyItem' => 1,
                        'PrcItem' => 15000,
                    ],
                ],
            ],
            // 16 - Factura exenta: 1 servicio
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 34,
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Desarrollo y mantención webapp agosto',
                        'QtyItem' => 1,
                        'PrcItem' => 950000,
                    ],
                ],
            ],
            // 17 - Factura exenta: 2 servicios
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 34,
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Desarrollo y mantención webapp agosto',
                        'QtyItem' => 1,
                        'PrcItem' => 950000,
                    ],
                    [
                        'NmbItem' => 'Configuración en terreno de servidor web',
                        'QtyItem' => 1,
                        'PrcItem' => 80000,
                    ],
                ],
            ],
            // 18 - Factura exenta: 1 servicio de capacitación por horas
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 34,
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Capacitación aplicación web',
                        'QtyItem' => 8,
                        'UnmdItem' => 'Hora',
                        'PrcItem' => 25000,
                    ],
                ],
            ],
            // 19 - Factura exenta: 1 servicio de desarrollo por horas más capacitación
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 34,
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Desarrollo nueva funcionalidad',
                        'QtyItem' => 16,
                        'UnmdItem' => 'Hora',
                        'PrcItem' => 14000,
                    ],
                    [
                        'NmbItem' => 'Capacitación nueva funcionalidad',
                        'QtyItem' => 2,
                        'UnmdItem' => 'Hora',
                        'PrcItem' => 25000,
                    ],
                ],
            ],
            // 20 - Factura exenta: 1 servicio con descuento global del 50%
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 34,
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Certificación facturación electrónica',
                        'QtyItem' => 1,
                        'PrcItem' => 599000,
                    ],
                ],
                'DscRcgGlobal' => [
                    'TpoMov' => 'D',
                    'TpoValor' => '%',
                    'ValorDR' => 50,
                    'IndExeDR' => 1,
                ]
            ],
            // 21 - Factura exenta: 2 servicios, uno con descuento del 50%
            [
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => 34,
                    ],
                ],
                'Detalle' => [
                    [
                        'NmbItem' => 'Desarrolo interfaces para API LibreDTE',
                        'QtyItem' => 40,
                        'UnmdItem' => 'Hora',
                        'PrcItem' => 14000,
                    ],
                    [
                        'NmbItem' => 'Capacitación API facturación electrónica',
                        'QtyItem' => 4,
                        'UnmdItem' => 'Hora',
                        'PrcItem' => 25000,
                    ],
                    [
                        'NmbItem' => 'Certificación facturación electrónica',
                        'QtyItem' => 1,
                        'PrcItem' => 599000,
                        'DescuentoPct' => 50,
                    ],
                ],
            ],
        ];
        # Guardamos una copia del json
        file_put_contents(__DIR__ . '/../../resources/assets/json/simulacion.json', json_encode($documentos));
        $this->config = Configuration::getInstance('folios-33', __DIR__ . '/../../resources/assets/xml/folios/33.xml');
        $this->firma = new FirmaElectronica(['file' => __DIR__ . '/../../resources/assets/certs/cert.pfx', 'pass' => env('FIRMA_PASS')]);
        $this->folios = [
            Dte::FACTURA_ELECTRONICA         => new Folios(file_get_contents(Configuration::getInstance('folios-' . Dte::FACTURA_ELECTRONICA, __DIR__ . '/../../resources/assets/xml/folios/33.xml')->getFilename())),
            Dte::NOTA_DE_CREDITO_ELECTRONICA => new Folios(file_get_contents(Configuration::getInstance('folios-' . Dte::NOTA_DE_CREDITO_ELECTRONICA, __DIR__ . '/../../resources/assets/xml/folios/61.xml')->getFilename())),
            Dte::NOTA_DE_DEBITO_ELECTRONICA  => new Folios(file_get_contents(Configuration::getInstance('folios-' . Dte::NOTA_DE_DEBITO_ELECTRONICA, __DIR__ . '/../../resources/assets/xml/folios/56.xml')->getFilename())),
            Dte::FACTURA_EXENTA_ELECTRONICA  => new Folios(file_get_contents(Configuration::getInstance('folios-' . Dte::FACTURA_EXENTA_ELECTRONICA, __DIR__ . '/../../resources/assets/xml/folios/34.xml')->getFilename())),
        ];
        $this->certification = new SimulationBuilder(
            $this->firma,
            new JsonSource(file_get_contents(__DIR__ . '/../../resources/assets/json/simulacion.json')),
            $this->folios,
            $emisor,
            $receptor
        );
    }

    /**
     * @test
     * @author David Lopez <dlopez@hsd.cl>
     */
    public function canInstance(): SimulationBuilder
    {
        $this->assertInstanceOf(SimulationBuilder::class, $this->certification);

        return $this->certification;
    }

    /**
     * @test
     * @depends canInstance
     * @param SimulationBuilder $certification
     * @return SimulationBuilder
     * @author  David Lopez <dlopez@hsd.cl>
     */
    public function canParse(SimulationBuilder $certification): SimulationBuilder
    {

        $this->assertInstanceOf(SimulationBuilder::class, $certification->parse($this->startFolios));

        return $certification;
    }

    /**
     * @test
     * @depends canParse
     * @param SimulationBuilder $certification
     * @return SimulationBuilder
     * @throws \HSDCL\DteCl\Util\Exception
     * @author David Lopez <dlopez@hsd.cl>
     */
    public function canStampAndSign(SimulationBuilder $certification): SimulationBuilder
    {
        $this->assertInstanceOf(SimulationBuilder::class, $certification->setStampAndSign());

        return $certification;
    }

    /**
     * @test
     * @param SimulationBuilder $certification
     * @author  David Lopez <dlopez@hsd.cl>
     */
    public function canBuild()
    {
        $certification = $this->certification->build($this->startFolios, $this->caratula);
        $this->assertInstanceOf(SimulationBuilder::class, $certification);

        return $certification;
    }

    /**
     * @test
     * @param SimulationBuilder $certification
     * @version 4/2/21
     * @author  David Lopez <dlopez@hsd.cl>
     * @depends canBuild
     */
    public function canExport(SimulationBuilder $certification)
    {
        $file = Str::uuid() . '.xml';
        $certification->export($file);
        $this->assertFileExists($file);
    }

    /**
     * @test
     * @author David Lopez <dlopez@hsd.cl>
     */
    public function canExportToPdf()
    {
        $this->assertTrue(
            BasicCertificationBuilder::exportToPdf(
                '/home/dlopez/Projects/Php/dte-cl/resources/assets/xml/simulacion/1.xml',
                __DIR__ . '/../../resources/assets/img/logo.png',
                __DIR__ . '/../../pdf/6_simulacion/'
            ))
        ;
    }
}

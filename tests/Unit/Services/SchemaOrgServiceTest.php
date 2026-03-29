<?php

namespace Tests\Unit\Services;

use App\Services\SchemaOrgService;
use Tests\TestCase;

class SchemaOrgServiceTest extends TestCase
{
    public function test_parse_schema_org_extracts_correct_data()
    {
        $jsonLd = [
            [
                '@type' => 'Product',
                'name' => 'Test Product',
                'description' => 'Test Description',
                'image' => 'https://example.com/image.jpg',
                'offers' => [
                    'price' => '19.99',
                    'priceCurrency' => 'USD',
                ],
            ],
        ];

        $collection = collect($jsonLd);

        $this->assertEquals('Test Product', SchemaOrgService::parseSchemaOrg($collection, 'title'));
        $this->assertEquals('Test Description', SchemaOrgService::parseSchemaOrg($collection, 'description'));
        $this->assertEquals('19.99', SchemaOrgService::parseSchemaOrg($collection, 'price'));
        $this->assertEquals('USD', SchemaOrgService::parseSchemaOrg($collection, 'price_currency'));
        $this->assertEquals('https://example.com/image.jpg', SchemaOrgService::parseSchemaOrg($collection, 'image'));
    }

    public function test_parse_schema_org_handles_different_price_formats()
    {
        // lowPrice
        $jsonLd = [
            [
                '@type' => 'Product',
                'offers' => [
                    'lowPrice' => '15.00',
                ],
            ],
        ];
        $this->assertEquals('15.00', SchemaOrgService::parseSchemaOrg(collect($jsonLd), 'price'));

        // priceSpecification
        $jsonLd = [
            [
                '@type' => 'Product',
                'offers' => [
                    'priceSpecification' => [
                        'price' => '25.00',
                    ],
                ],
            ],
        ];
        $this->assertEquals('25.00', SchemaOrgService::parseSchemaOrg(collect($jsonLd), 'price'));
    }

    public function test_parse_schema_org_handles_image_array()
    {
        $jsonLd = [
            [
                '@type' => 'Product',
                'image' => [
                    'https://example.com/image1.jpg',
                    'https://example.com/image2.jpg',
                ],
            ],
        ];
        $this->assertEquals('https://example.com/image1.jpg', SchemaOrgService::parseSchemaOrg(collect($jsonLd), 'image'));
    }

    public function test_parse_schema_org_returns_null_if_no_product_found()
    {
        $jsonLd = [
            [
                '@type' => 'NewsArticle',
                'name' => 'Not a product',
            ],
        ];
        $this->assertNull(SchemaOrgService::parseSchemaOrg(collect($jsonLd), 'title'));
    }

    public function test_parse_schema_org_handles_availability_formats()
    {
        // Simple string availability
        $jsonLd = [
            [
                '@type' => 'Product',
                'offers' => [
                    'availability' => 'https://schema.org/InStock',
                ],
            ],
        ];
        $this->assertEquals('https://schema.org/InStock', SchemaOrgService::parseSchemaOrg(collect($jsonLd), 'availability'));

        // Array availability
        $jsonLd = [
            [
                '@type' => 'Product',
                'offers' => [
                    '0' => [
                        'availability' => 'https://schema.org/OutOfStock',
                    ],
                ],
            ],
        ];
        $this->assertEquals('https://schema.org/OutOfStock', SchemaOrgService::parseSchemaOrg(collect($jsonLd), 'availability'));

        // Array of strings availability
        $jsonLd = [
            [
                '@type' => 'Product',
                'offers' => [
                    'availability' => ['https://schema.org/InStock', 'something else'],
                ],
            ],
        ];
        $this->assertEquals('https://schema.org/InStock', SchemaOrgService::parseSchemaOrg(collect($jsonLd), 'availability'));

        // Non-string availability (e.g., an object/array that is not normalized)
        $jsonLd = [
            [
                '@type' => 'Product',
                'offers' => [
                    'availability' => ['type' => 'ItemAvailability', 'value' => 'InStock'],
                ],
            ],
        ];
        // Currently it would return the array if we just use data_get directly.
        // We want it to return null if it's not a string or first element is not a string.
        $this->assertNull(SchemaOrgService::parseSchemaOrg(collect($jsonLd), 'availability'));
    }
}

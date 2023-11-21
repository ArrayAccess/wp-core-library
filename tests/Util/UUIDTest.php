<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Tests\Libraries\Core\Util;

use ArrayAccess\WP\Libraries\Core\Exceptions\InvalidArgumentException;
use ArrayAccess\WP\Libraries\Core\Util\UUID;
use PHPUnit\Framework\TestCase;
use function gmdate;
use function hexdec;
use function strtotime;
use function time;

/**
 * Unit test for class: UUID
 */
class UUIDTest extends TestCase
{
    public function testExtractUUIDPart()
    {
        $uuidV4 = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $parse = UUID::extractUUIDPart($uuidV4);
        $this->assertSame(
            $uuidV4,
            $parse[0],
            'Should return a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11 for offset 0'
        );
        $this->assertSame(
            'a0eebc99',
            $parse[1],
            'Should return a0eebc99 for offset 1'
        );
        $this->assertCount(
            6,
            $parse,
            'Should return 6 for count'
        );
        $uuidV6 = 'a0eebc99-9c0b-6ef8-bb6d-6bb9bd380a11';
        $this->assertNull(
            UUID::extractUUIDPart($uuidV6),
            'Should return null for invalid UUID'
        );
    }
    public function testExtractUUID()
    {
        $uuidV4 = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $parse = UUID::extractUUID($uuidV4);
        $this->assertArrayHasKey(
            'time_low',
            $parse,
            'Should return an array with key time_low'
        );
        $this->assertArrayHasKey(
            'time_mid',
            $parse,
            'Should return an array with key time_mid'
        );
        $this->assertArrayHasKey(
            'time_hi_and_version',
            $parse,
            'Should return an array with key time_hi_and_version'
        );
        $this->assertArrayHasKey(
            'clock_seq_hi_and_reserved',
            $parse,
            'Should return an array with key clock_seq_hi_and_reserved'
        );
        $this->assertArrayHasKey(
            'clock_seq_low',
            $parse,
            'Should return an array with key clock_seq_low'
        );
        $this->assertArrayHasKey(
            'node',
            $parse,
            'Should return an array with key node'
        );
        $this->assertArrayHasKey(
            'version',
            $parse,
            'Should return an array with key version'
        );
        $this->assertArrayHasKey(
            'variant',
            $parse,
            'Should return an array with key variant'
        );
        $this->assertSame(
            hexdec('a0eebc99'),
            $parse['time_low'],
            'Should return hexdec("a0eebc99") for time_low'
        );
    }

    public function testIsValid()
    {
        $this->assertTrue(
            UUID::isValid('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            'Should return true for valid UUID'
        );
        $this->assertFalse(
            UUID::isValid('00000000-0000-0000-0000-000000000000'),
            'Should return false for invalid UUID'
        );
    }

    public function testCalculateNamespaceAndName()
    {
        $uuidV4 = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $name = 'hello';
        $calculate = UUID::calculateNamespaceAndName($uuidV4, $name, UUID::UUID_TYPE_MD5);
        $this->assertSame(
            '91902d646bc621edb84a75bb11c300bc',
            $calculate,
            'Should return 91902d646bc621edb84a75bb11c300bc for calculate'
        );
        $calculate = UUID::calculateNamespaceAndName($uuidV4, $name, UUID::UUID_TYPE_SHA1);
        $this->assertSame(
            '11865854467e98510f48106d854a121a317ae71e',
            $calculate,
            'Should return 11865854467e98510f48106d854a121a317ae71e for calculate'
        );
        $uuidV3 = 'a0eebc99-9c0b-31d1-0000-000000000000';
        $calculate = UUID::calculateNamespaceAndName($uuidV3, $name);
        $this->assertSame(
            '5f6a43c84026c2693781b32fca039835',
            $calculate,
            'Should return 5f6a43c84026c2693781b32fca039835 for calculate'
        );
        $uuidV5 = 'a0eebc99-9c0b-51d1-0000-000000000000';
        $calculate = UUID::calculateNamespaceAndName($uuidV5, $name);
        $this->assertSame(
            '06a5f33277bcafaf2827ea7cbfa1ee005fcc1432',
            $calculate,
            'Should return 06a5f33277bcafaf2827ea7cbfa1ee005fcc1432 for calculate'
        );
    }

    public function testParse()
    {
        $this->assertNull(
            UUID::parse('00000000-0000-0000-0000-000000000000'),
            'Should return null for an invalid UUID'
        );
        $uuidV4 = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $parseUUIDv4 = UUID::parse($uuidV4);
        $this->assertIsArray(
            $parseUUIDv4,
            'Should return an array for a valid UUID'
        );
        // assert list key return from parse
        $this->assertArrayHasKey(
            'time_low',
            $parseUUIDv4,
            'Should return an array with key time_low'
        );
        $this->assertArrayHasKey(
            'time_mid',
            $parseUUIDv4,
            'Should return an array with key time_mid'
        );
        $this->assertArrayHasKey(
            'time_hi_and_version',
            $parseUUIDv4,
            'Should return an array with key time_hi_and_version'
        );
        $this->assertArrayHasKey(
            'clock_seq_hi_and_reserved',
            $parseUUIDv4,
            'Should return an array with key clock_seq_hi_and_reserved'
        );
        $this->assertArrayHasKey(
            'clock_seq_low',
            $parseUUIDv4,
            'Should return an array with key clock_seq_low'
        );
        $this->assertArrayHasKey(
            'node',
            $parseUUIDv4,
            'Should return an array with key node'
        );
        $this->assertArrayHasKey(
            'version',
            $parseUUIDv4,
            'Should return an array with key version'
        );
        $this->assertArrayHasKey(
            'variant',
            $parseUUIDv4,
            'Should return an array with key variant'
        );

        // assert value return from parse
        $this->assertSame(
            hexdec('a0eebc99'),
            $parseUUIDv4['time_low'],
            'Should return hexdec("a0eebc99") for time_low'
        );
    }

    public function testVersion()
    {
        $uuidV1 = 'a0eebc99-9c0b-11d1-0000-000000000000';
        $this->assertSame(
            1,
            UUID::version($uuidV1),
            'Should return 1 for version 1'
        );
        $uuidV2 = 'a0eebc99-9c0b-21d1-0000-000000000000';
        $this->assertSame(
            2,
            UUID::version($uuidV2),
            'Should return 2 for version 2'
        );
        $uuidV3 = 'a0eebc99-9c0b-31d1-0000-000000000000';
        $this->assertSame(
            3,
            UUID::version($uuidV3),
            'Should return 3 for version 3'
        );
        $uuidV4 = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $this->assertSame(
            4,
            UUID::version($uuidV4),
            'Should return 4 for version 4'
        );
        $uuidV5 = 'a0eebc99-9c0b-51d1-0000-000000000000';
        $this->assertSame(
            5,
            UUID::version($uuidV5),
            'Should return 5 for version 5'
        );
        $uuidV6 = 'a0eebc99-9c0b-61d1-0000-000000000000';
        // uuid v6 is unsupported it should be return null
        $this->assertNull(
            UUID::version($uuidV6),
            'Should return null for version 6'
        );
    }

    public function testV5()
    {
        $uuidV5 = UUID::v5('6ba7b810-9dad-11d1-80b4-00c04fd430c8', 'hello');
        $this->assertSame(
            '9342d47a-1bab-5709-9869-c840b2eac501',
            $uuidV5,
            'Should return 9342d47a-1bab-5709-9869-c840b2eac501 for v5'
        );
    }

    public function testV1()
    {
        $uuidV1 = UUID::v1();
        $this->assertIsString(
            $uuidV1,
            'Should return a string for v1'
        );
        $this->assertSame(
            1,
            UUID::version($uuidV1),
            'Should return 1 for version 1'
        );
        $parse = UUID::parse($uuidV1);
        $this->assertSame(
            gmdate('Y-m-d H:i', time()),
            gmdate('Y-m-d H:i', strtotime($parse['contents_time'])),
            'Should return current date for contents_time'
        );
    }

    public function testV2()
    {
        $uuidV2 = UUID::v2();
        $this->assertIsString(
            $uuidV2,
            'Should return a string for v2'
        );
        $this->assertSame(
            2,
            UUID::version($uuidV2),
            'Should return 2 for version 2'
        );
    }

    public function testV3()
    {
        $uuidV3 = UUID::v3('6ba7b810-9dad-11d1-80b4-00c04fd430c8', 'hello');
        $this->assertSame(
            '0bacede4-4014-3f9d-b720-173f68a1c933',
            $uuidV3,
            'Should return 0bacede4-4014-3f9d-b720-173f68a1c933 for v3'
        );
    }

    public function testV4()
    {
        $uuidV4 = UUID::v4();
        $this->assertIsString(
            $uuidV4,
            'Should return a string for v4'
        );
        $this->assertSame(
            4,
            UUID::version($uuidV4),
            'Should return 4 for version 4'
        );
    }

    public function testIntegerId()
    {
        $uuidV1 = UUID::v1();
        $integerId = UUID::integerId($uuidV1);
        $this->assertSame(
            $integerId,
            UUID::parse($uuidV1)['single_integer'],
            'Should return 1 for integerId'
        );
        $uuidV1 = '789e83a0-883d-11ee-8d09-325096b39f47';
        $this->assertSame(
            '160330412112166278656301647119523225415',
            UUID::integerId($uuidV1),
            'Should return 160330412112166278656301647119523225415 for integerId'
        );
        $uuidV1 = 'b535ab40-883d-11ee-85a3-325096b39f47';
        $this->assertSame(
            '240868932375380534699345610985998360391',
            UUID::integerId($uuidV1),
            'Should return 240868932375380534699345610985998360391 for integerId'
        );
        $invalidUUid = '00000000-0000-0000-0000-000000000000';
        $this->assertNull(
            UUID::integerId($invalidUUid),
            'Should return null for integerId'
        );
    }


    public function testGenerate()
    {
        $uuidV1 = UUID::generate(1);
        $this->assertIsString(
            $uuidV1,
            'Should return a string for v1'
        );
        $this->assertSame(
            1,
            UUID::version($uuidV1),
            'Should return 1 for version 1'
        );
        $parse = UUID::parse($uuidV1);
        $this->assertSame(
            gmdate('Y-m-d H:i', time()),
            gmdate('Y-m-d H:i', strtotime($parse['contents_time'])),
            'Should return current date for contents_time'
        );
        $uuidV2 = UUID::generate(2);
        $this->assertIsString(
            $uuidV2,
            'Should return a string for v2'
        );
        $this->assertSame(
            2,
            UUID::version($uuidV2),
            'Should return 2 for version 2'
        );
        $uuidV3 = UUID::generate(
            3,
            hash: UUID::calculateNamespaceAndName(
                '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'hello'
            )
        );
        $this->assertSame(
            '9342d47a-1bab-3709-9869-c840b2eac501',
            $uuidV3,
            'Should return 9342d47a-1bab-3709-9869-c840b2eac501 for v3'
        );
        $uuidV4 = UUID::generate(4);
        $this->assertIsString(
            $uuidV4,
            'Should return a string for v4'
        );
        $this->assertSame(
            4,
            UUID::version($uuidV4),
            'Should return 4 for version 4'
        );
        $uuidV5 = UUID::generate(
            5,
            hash: UUID::calculateNamespaceAndName(
                '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'hello'
            )
        );
        $this->assertSame(
            '9342d47a-1bab-5709-9869-c840b2eac501',
            $uuidV5,
            'Should return 9342d47a-1bab-5709-9869-c840b2eac501 for v5'
        );
        $this->expectException(InvalidArgumentException::class);
        UUID::generate(5, type: UUID::UUID_TYPE_MD5, hash: 'invalid hash');
    }
}

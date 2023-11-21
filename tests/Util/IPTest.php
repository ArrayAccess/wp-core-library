<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Tests\Libraries\Core\Util;

use ArrayAccess\WP\Libraries\Core\Util\IP;
use PHPUnit\Framework\TestCase;

/**
 * Unit test to test IP utility class
 */
class IPTest extends TestCase
{

    public function testIsValidIpv4()
    {
        $this->assertTrue(
            IP::isValidIpv4('192.168.0.1'),
            'IPv4 address 192.168.0.1 is valid'
        );
        $this->assertFalse(
            IP::isValidIpv4('192.168.0.256'),
            'IPv4 address 192.168.0.256 is invalid'
        );
    }

    public function testFilterIpv4()
    {
        $ip4 = '192.168.0.1';
        $this->assertSame(
            $ip4,
            IP::filterIpv4($ip4),
            'IPv4 address IP::filterIpv4("192.168.0.1") same with 192.168.0.1'
        );
        $this->assertNull(
            IP::filterIpv4('192.168.0.256'),
            'IPv4 address IP::filterIpv4("192.168.0.256") is null'
        );
    }

    public function testIsLocalIP4()
    {
        $this->assertTrue(
            IP::isLocalIP4('192.168.0.1'),
            'IPv4 address 192.168.0.1 is local'
        );
        $this->assertFalse(
            IP::isLocalIP4('8.8.8.8'),
            'IPv4 address 8.8.8.8 is not local'
        );
    }

    public function testIsValidIpv6()
    {
        $ipV6 = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
        $this->assertTrue(
            IP::isValidIpv6($ipV6),
            'IPv6 address ' . $ipV6 . ' is valid'
        );
        $invalidIpv6 = '2001:0db8:8ua3:0000:0000:8a2e:0370:7334:0000';
        $this->assertFalse(
            IP::isValidIpv6($invalidIpv6),
            'IPv6 address ' . $invalidIpv6 . ' is invalid'
        );
    }

    public function testIpv4CIDRToRange()
    {
        $cidr = '192.168.0.1/32';
        $ipCIDR = IP::ipv4CIDRToRange($cidr);
        $this->assertIsArray(
            $ipCIDR,
            'IPv4 CIDR ' . $cidr . ' is array'
        );
        $this->assertCount(
            2,
            $ipCIDR,
            'IPv4 CIDR ' . $cidr . ' is array with 2 elements'
        );
        $this->assertArrayHasKey(
            0,
            $ipCIDR,
            'IPv4 CIDR ' . $cidr . ' is array with key 0'
        );
        $this->assertArrayHasKey(
            1,
            $ipCIDR,
            'IPv4 CIDR ' . $cidr . ' is array with key 1'
        );
        $this->assertSame(
            $ipCIDR[0],
            $ipCIDR[1],
            'IPv4 CIDR ' . $cidr . ' is array with same value'
        );
        $cidr = '192.168.0.1/24';
        $ipCIDR = IP::ipv4CIDRToRange($cidr);
        $this->assertIsArray(
            $ipCIDR,
            'IPv4 CIDR ' . $cidr . ' is array'
        );
        $this->assertCount(
            2,
            $ipCIDR,
            'IPv4 CIDR ' . $cidr . ' is array with 2 elements'
        );
        $this->assertArrayHasKey(
            0,
            $ipCIDR,
            'IPv4 CIDR ' . $cidr . ' is array with key 0'
        );
        $this->assertArrayHasKey(
            1,
            $ipCIDR,
            'IPv4 CIDR ' . $cidr . ' is array with key 1'
        );
        $this->assertNotSame(
            $ipCIDR[0],
            $ipCIDR[1],
            'IPv4 CIDR ' . $cidr . ' is array with different value'
        );
        $this->assertSame(
            '192.168.0.0',
            $ipCIDR[0],
            'IPv4 CIDR ' . $cidr . ' is array with 192.168.0.0'
        );
        $this->assertSame(
            '192.168.1.0',
            $ipCIDR[1],
            'IPv4 CIDR ' . $cidr . ' is array with 192.168.1.0'
        );
        $this->assertNull(
            IP::ipv4CIDRToRange('0/2'),
            'IPv4 CIDR 0/2 is null'
        );
    }

    public function testIpv6CIDRToRange()
    {
        $ipv6 = '::1';
        $cidr = $ipv6 . '/128';
        $ipCIDR = IP::ipv6CIDRToRange($cidr);
        $this->assertIsArray(
            $ipCIDR,
            'IPv6 CIDR ' . $cidr . ' is array'
        );
        $this->assertCount(
            2,
            $ipCIDR,
            'IPv6 CIDR ' . $cidr . ' is array with 2 elements'
        );
        $this->assertArrayHasKey(
            0,
            $ipCIDR,
            'IPv6 CIDR ' . $cidr . ' is array with key 0'
        );
        $this->assertArrayHasKey(
            1,
            $ipCIDR,
            'IPv6 CIDR ' . $cidr . ' is array with key 1'
        );
        $this->assertSame(
            $ipCIDR[0],
            $ipCIDR[1],
            'IPv6 CIDR ' . $cidr . ' is array with same value'
        );
        $cidr = $ipv6 . '/64';
        $ipCIDR = IP::ipv6CIDRToRange($cidr);
        $this->assertIsArray(
            $ipCIDR,
            'IPv6 CIDR ' . $cidr . ' is array'
        );
        $this->assertCount(
            2,
            $ipCIDR,
            'IPv6 CIDR ' . $cidr . ' is array with 2 elements'
        );
        $this->assertArrayHasKey(
            0,
            $ipCIDR,
            'IPv6 CIDR ' . $cidr . ' is array with key 0'
        );
        $this->assertArrayHasKey(
            1,
            $ipCIDR,
            'IPv6 CIDR ' . $cidr . ' is array with key 1'
        );
        $this->assertNotSame(
            $ipCIDR[0],
            $ipCIDR[1],
            'IPv6 CIDR ' . $cidr . ' is array with different value'
        );
        $this->assertSame(
            '::1',
            $ipCIDR[0],
            'IPv6 CIDR ' . $cidr . ' is array with ::1'
        );
        $this->assertSame(
            '::ffff:ffff:ffff:ffff',
            $ipCIDR[1],
            'IPv6 CIDR ' . $cidr . ' is array with ::ffff:ffff:ffff:ffff'
        );
    }

    public function testVersion()
    {
        $this->assertSame(
            4,
            IP::version('192.168.0.1'),
            'IPv4 address 192.168.0.1 is version 4'
        );
        $this->assertSame(
            6,
            IP::version('2001:0db8:85a3:0000:0000:8a2e:0370:7334'),
            'IPv6 address 2001:0db8:85a3:0000:0000:8a2e:0370:7334 is version 6'
        );
    }
}

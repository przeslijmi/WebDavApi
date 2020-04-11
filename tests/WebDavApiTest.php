<?php declare(strict_types=1);

namespace Przeslijmi\WebDavApi;

use Exception;
use PHPUnit\Framework\TestCase;
use Przeslijmi\WebDavApi\WebDavApi;

/**
 * Methods for testing WebDavApi class.
 */
final class WebDavApiTest extends TestCase
{

    /**
     * Test if creating of instance works.
     *
     * @return void
     */
    public function testIfCreationWorks() : void
    {

        // Create API.
        $api = new WebDavApi('testing.uri', 555);

        // Test.
        $this->assertFalse($api->hasLogin());

        // Add credentials.
        $api->setLogin('testing_user', 'testing_pass');

        // Test.
        $this->assertTrue($api->hasLogin());
        $this->assertEquals('testing.uri/:555', $api->getUrl());
        $this->assertEquals('testing_user', $api->getUser());
        $this->assertEquals('testing_pass', $api->getPassword());

        // Test if this will throw.
        $this->expectException(Exception::class);
        $api->readContents();
    }

    /**
     * Test if uploading files works.
     *
     * @return void
     */
    public function testIfOperationsWork() : void
    {

        // Lvd.
        $resourcesDir    = dirname(__DIR__) . '/resources/';
        $testDir         = 'test' . rand(10000, 99999);
        $isFolderPresent = false;

        // Create API with credentials.
        $api = new WebDavApi(
            PRZESLIJMI_WEBDAVAPI_TESTING_API_URI,
            PRZESLIJMI_WEBDAVAPI_TESTING_API_PORT
        );
        $api->setLogin(
            PRZESLIJMI_WEBDAVAPI_TESTING_API_USER,
            PRZESLIJMI_WEBDAVAPI_TESTING_API_PASS
        );
        $api->setIgnoreSsl(true);

        // Add folder.
        $api->createFolder($testDir);

        // Read contents of dir.
        $api->readContents();

        // Get contents after creating folder.
        $contents      = $api->getContents();
        $countContents = count($contents);

        // Test if array is returned.
        $this->assertEquals('array', gettype($contents));
        $this->assertTrue($api->doesContentExists($testDir . '/'), 'If test directory exists?');

        // Upload file.
        $api->uploadFile($resourcesDir . 'test.pdf', $testDir . '/test.pdf');

        // Read contents of dir.
        $api->readContents($testDir . '/');

        // Check if has been uploaded.
        $this->assertTrue($api->doesContentExists('test.pdf'), 'If PDF test exists?');

        // Move file.
        $api->moveFile($testDir . '/test.pdf', $testDir . '/testMoved.pdf');

        // Read contents of dir.
        $api->readContents($testDir . '/');

        // Check if has been uploaded.
        $this->assertTrue($api->doesContentExists('testMoved.pdf'), 'If moved PDF test exists?');
        $this->assertFalse($api->doesContentExists('testNonexisting.pdf'), 'If nonexisting file exists?');

        // Delete PDF.
        $api->delete($testDir . '/testMoved.pdf');

        // Read contents of dir.
        $api->readContents($testDir . '/');

        // Test if dir is empty.
        $this->assertEquals(0, count($api->getContents()), 'If dir is now empty?');

        // Delete test dir.
        $api->delete($testDir);

        // Read contents of dir.
        $api->readContents();

        // Test if there is one less file then in the beginning.
        $this->assertEquals(( $countContents - 1 ), count($api->getContents()));
    }
}

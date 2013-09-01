<?php

namespace Gedmo\Uploadable;

use Tool\BaseTestCaseORM,
    Doctrine\Common\EventManager,
    Doctrine\Common\Util\Debug,
    Uploadable\Fixture\Entity\Image,
    Uploadable\Fixture\Entity\Article,
    Uploadable\Fixture\Entity\File,
    Uploadable\Fixture\Entity\FileWithoutPath,
    Uploadable\Fixture\Entity\FileWithSha1Name,
    Uploadable\Fixture\Entity\FileWithAlphanumericName,
    Uploadable\Fixture\Entity\FileWithCustomFilenameGenerator,
    Uploadable\Fixture\Entity\FileAppendNumber,
    Uploadable\Fixture\Entity\FileWithMaxSize,
    Uploadable\Fixture\Entity\FileWithAllowedTypes,
    Uploadable\Fixture\Entity\FileWithDisallowedTypes,
    Gedmo\Uploadable\Stub\UploadableListenerStub,
    Gedmo\Uploadable\Stub\MimeTypeGuesserStub,
    Gedmo\Uploadable\FileInfo\FileInfoArray;

/**
 * These are tests for Uploadable behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class UploadableEntityTest extends BaseTestCaseORM
{
    const IMAGE_CLASS = 'Uploadable\Fixture\Entity\Image';
    const ARTICLE_CLASS = 'Uploadable\Fixture\Entity\Article';
    const FILE_CLASS = 'Uploadable\Fixture\Entity\File';
    const FILE_APPEND_NUMBER_CLASS = 'Uploadable\Fixture\Entity\FileAppendNumber';
    const FILE_WITHOUT_PATH_CLASS = 'Uploadable\Fixture\Entity\FileWithoutPath';
    const FILE_WITH_SHA1_NAME_CLASS = 'Uploadable\Fixture\Entity\FileWithSha1Name';
    const FILE_WITH_ALPHANUMERIC_NAME_CLASS = 'Uploadable\Fixture\Entity\FileWithAlphanumericName';
    const FILE_WITH_CUSTOM_FILENAME_GENERATOR_CLASS = 'Uploadable\Fixture\Entity\FileWithCustomFilenameGenerator';
    const FILE_WITH_MAX_SIZE_CLASS = 'Uploadable\Fixture\Entity\FileWithMaxSize';
    const FILE_WITH_ALLOWED_TYPES_CLASS = 'Uploadable\Fixture\Entity\FileWithAllowedTypes';
    const FILE_WITH_DISALLOWED_TYPES_CLASS = 'Uploadable\Fixture\Entity\FileWithDisallowedTypes';

    /**
     * @var UploadableListener
     */
    private $listener;
    private $testFile;
    private $testFile2;
    private $testFile3;
    private $testFileWithoutExt;
    private $testFileWithSpaces;
    private $destinationTestDir;
    private $destinationTestFile;
    private $destinationTestFile2;
    private $destinationTestFile3;
    private $destinationTestFileWithoutExt;
    private $destinationTestFileWithSpaces;
    private $testFilename;
    private $testFilename2;
    private $testFilename3;
    private $testFilenameWithoutExt;
    private $testFilenameWithSpaces;
    private $testFileSize;
    private $testFileMimeType;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->listener = new UploadableListenerStub();
        $this->listener->setMimeTypeGuesser(new MimeTypeGuesserStub('text/plain'));

        $evm->addEventSubscriber($this->listener);
        $config = $this->getMockAnnotatedConfig();
        $this->em = $this->getMockSqliteEntityManager($evm, $config);
        $this->testFile = __DIR__.'/../../data/test.txt';
        $this->testFile2 = __DIR__.'/../../data/test2.txt';
        $this->testFile3 = __DIR__.'/../../data/test_3.txt';
        $this->testFileWithoutExt = __DIR__.'/../../data/test4';
        $this->testFileWithSpaces = __DIR__.'/../../data/test with spaces.txt';
        $this->destinationTestDir = __DIR__.'/../../temp/uploadable';
        $this->destinationTestFile = $this->destinationTestDir.'/test.txt';
        $this->destinationTestFile2 = $this->destinationTestDir.'/test2.txt';
        $this->destinationTestFile3 = $this->destinationTestDir.'/test_3.txt';
        $this->destinationTestFileWithoutExt = $this->destinationTestDir.'/test4';
        $this->destinationTestFileWithSpaces = $this->destinationTestDir.'/test with spaces';
        $this->testFilename = substr($this->testFile, strrpos($this->testFile, '/') + 1);
        $this->testFilename2 = substr($this->testFile2, strrpos($this->testFile2, '/') + 1);
        $this->testFilename3 = substr($this->testFile3, strrpos($this->testFile3, '/') + 1);
        $this->testFilenameWithoutExt = substr($this->testFileWithoutExt, strrpos($this->testFileWithoutExt, '/') + 1);
        $this->testFilenameWithSpaces= substr($this->testFileWithSpaces, strrpos($this->testFileWithSpaces, '/') + 1);
        $this->testFileSize = 4;
        $this->testFileMimeType = 'text/plain';

        $this->clearFilesAndDirectories();

        if (!is_dir($this->destinationTestDir)) {
            mkdir($this->destinationTestDir);
        };
    }

    public function tearDown()
    {
        $this->clearFilesAndDirectories();
    }

    public function testUploadableEntity()
    {
        // INSERTION of an Uploadable Entity

        // If there was no uploaded file, we do nothing
        $image = new Image();
        $image->setTitle('123');

        $this->em->persist($image);
        $this->em->flush();

        $this->assertNull($image->getFilePath());

        // If there is an uploaded file, we process it
        $fileInfo = $this->generateUploadedFile();

        $image2 = new Image();
        $image2->setTitle('456');
        $this->listener->addEntityFileInfo($image2, $fileInfo);

        $this->em->persist($image2);
        $this->em->flush();

        $this->em->refresh($image2);

        // We need to set this again because of the recent refresh
        $firstFile = $image2->getFilePath();

        $this->assertPathEquals($image2->getPath().'/'.$fileInfo['name'], $image2->getFilePath());
        $this->assertTrue(is_file($firstFile));
        $this->assertEquals($fileInfo['size'], $image2->getSize());
        $this->assertEquals($fileInfo['type'], $image2->getMime());

        // UPDATE of an Uploadable Entity

        // We change the "uploaded" file
        $fileInfo['tmp_name'] = $this->testFile2;
        $fileInfo['name'] = $this->testFilename2;

        // We use a FileInfoInterface instance here
        $this->listener->addEntityFileInfo($image2, new FileInfoArray($fileInfo));

        $this->em->flush();

        $this->em->refresh($image2);

        $lastFile = $image2->getFilePath();

        $this->assertPathEquals($image2->getPath().'/'.$fileInfo['name'], $image2->getFilePath());
        $this->assertTrue(is_file($lastFile));

        // First file should be removed on update
        $this->assertFalse(is_file($firstFile));

        // REMOVAL of an Uploadable Entity
        $this->em->remove($image2);
        $this->em->flush();

        $this->assertFalse(is_file($lastFile));
    }

    public function testUploadableEntityWithCompositePath()
    {
        // We set the default path on the listener
        $this->listener->setDefaultPath($this->destinationTestDir);

        // If there is an uploaded file, we process it
        $fileInfo = $this->generateUploadedFile();

        $image2 = new Image();
        $image2->setUseBasePath(true);
        $image2->setTitle('456');
        $this->listener->addEntityFileInfo($image2, $fileInfo);

        $this->em->persist($image2);
        $this->em->flush();

        $this->em->refresh($image2);

        // We need to set this again because of the recent refresh
        $firstFile = $image2->getFilePath();

        $this->assertPathEquals($image2->getPath($this->destinationTestDir).'/'.$fileInfo['name'], $image2->getFilePath());
        $this->assertTrue(is_file($firstFile));
        $this->assertEquals($fileInfo['size'], $image2->getSize());
        $this->assertEquals($fileInfo['type'], $image2->getMime());

        // UPDATE of an Uploadable Entity

        // We change the "uploaded" file
        $fileInfo['tmp_name'] = $this->testFile2;
        $fileInfo['name'] = $this->testFilename2;

        // We use a FileInfoInterface instance here
        $this->listener->addEntityFileInfo($image2, new FileInfoArray($fileInfo));

        $this->em->flush();

        $this->em->refresh($image2);

        $lastFile = $image2->getFilePath();

        $this->assertPathEquals($image2->getPath($this->destinationTestDir).'/'.$fileInfo['name'], $image2->getFilePath());
        $this->assertTrue(is_file($lastFile));

        // First file should be removed on update
        $this->assertFalse(is_file($firstFile));

        // REMOVAL of an Uploadable Entity
        $this->em->remove($image2);
        $this->em->flush();

        $this->assertFalse(is_file($lastFile));
    }

    public function testEntityWithUploadableEntities()
    {
        $artRepo = $this->em->getRepository(self::ARTICLE_CLASS);
        $article = new Article();
        $article->setTitle('Test');

        $file1 = new File();
        $file2 = new File();
        $file3 = new File();

        $article->addFile($file1);
        $article->addFile($file2);
        $article->addFile($file3);

        $filesArrayIndex = 'file';

        $fileInfo = $this->generateUploadedFile($filesArrayIndex);
        $fileInfo2 = $this->generateUploadedFile($filesArrayIndex);
        $fileInfo3 = $this->generateUploadedFile($filesArrayIndex);

        $this->listener->addEntityFileInfo($file1, $fileInfo);
        $this->listener->addEntityFileInfo($file2, $fileInfo2);
        $this->listener->addEntityFileInfo($file3, $fileInfo3);

        $this->em->persist($article);

        $this->em->flush();

        $art = $artRepo->findOneByTitle('Test');
        $files = $art->getFiles();
        $file1Path = $file1->getPath().'/'.$fileInfo['name'];
        $file2Path = $file2->getPath().'/'.$fileInfo['name'];
        $file3Path = $file3->getPath().'/'.$fileInfo['name'];

        $this->assertPathEquals($file1Path, $files[0]->getFilePath());
        $this->assertPathEquals($file2Path, $files[1]->getFilePath());
        $this->assertPathEquals($file3Path, $files[2]->getFilePath());
    }

    /**
     * @expectedException Gedmo\Exception\UploadableNoPathDefinedException
     */
    public function testNoPathDefinedOnEntityOrListenerThrowsException()
    {
        $file = new FileWithoutPath();

        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();
    }

    public function testNoPathDefinedOnEntityButDefinedOnListenerUsesDefaultPath()
    {
        // We set the default path on the listener
        $this->listener->setDefaultPath($this->destinationTestDir);

        $file = new FileWithoutPath();
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $this->em->refresh($file);

        $this->assertPathEquals($this->destinationTestFile, $file->getFilePath());
    }

    public function testCallbackIsCalledIfItsSetOnEntity()
    {
        $file = new File();
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $this->assertTrue($file->callbackWasCalled);
    }

    /**
     * @dataProvider uploadExceptionsProvider
     */
    public function testUploadExceptions($error, $exceptionClass)
    {
        $this->setExpectedException($exceptionClass);

        $file = new File();
        $fileInfo = $this->generateUploadedFile();
        $fileInfo['error'] = $error;

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();
    }

    public function testSettingAnotherDefaultFileInfoClass()
    {
        $fileInfoStubClass = 'Gedmo\Uploadable\Stub\FileInfoStub';

        $this->listener->setDefaultFileInfoClass($fileInfoStubClass);

        $file = new File();
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);
        $fileInfo = $this->listener->getEntityFileInfo($file);

        $this->assertInstanceOf($fileInfoStubClass, $fileInfo);
    }

    public function testFileWithFilenameSha1Generator()
    {
        $file = new FileWithSha1Name();
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $this->em->refresh($file);

        $sha1String = substr($file->getFilePath(), strrpos($file->getFilePath(), '/') + 1);
        $sha1String = str_replace('.txt', '', $sha1String);

        $this->assertRegExp('/[a-z0-9]{40}/', $sha1String);
    }

    public function testFileWithFilenameAlphanumericGenerator()
    {
        $file = new FileWithAlphanumericName();
        $fileInfo = $this->generateUploadedFile('image', $this->testFile3, $this->testFilename3);

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $this->em->refresh($file);

        $filename = substr($file->getFilePath(), strrpos($file->getFilePath(), '/') + 1);

        $this->assertEquals('test-3.txt', $filename);
    }

    public function testFileWithCustomFilenameGenerator()
    {
        $file = new FileWithCustomFilenameGenerator();
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $this->em->refresh($file);

        $filename = substr($file->getFilePath(), strrpos($file->getFilePath(), '/') + 1);

        $this->assertEquals('123.txt', $filename);
    }

    public function testUploadFileWithoutExtension()
    {
        $file = new File();
        $fileInfo = $this->generateUploadedFile('image', $this->testFileWithoutExt, $this->testFilenameWithoutExt);

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $this->em->refresh($file);

        $filePath = $file->getPath().'/'.$fileInfo['name'];

        $this->assertPathEquals($filePath, $file->getFilePath());
    }

    /**
     * @expectedException Gedmo\Exception\UploadableFileAlreadyExistsException
     */
    public function testFileAlreadyExistsException()
    {
        $file = new Image();
        $file->setTitle('test');
        $fileInfo = $this->generateUploadedFile('image', $this->testFileWithoutExt, $this->testFilenameWithoutExt);

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->flush();
    }

    public function test_removeFile_ifItsNotAFileThenReturnFalse()
    {
        $this->assertFalse($this->listener->removeFile('non_existent_file'));
    }

    public function test_moveFile_usingAppendNumberOptionAppendsNumberToFilenameIfItAlreadyExists()
    {
        $file = new FileAppendNumber();
        $file2 = new FileAppendNumber();

        $file->setTitle('test');
        $file2->setTitle('test2');

        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $this->listener->addEntityFileInfo($file2, $fileInfo);

        $this->em->persist($file2);
        $this->em->flush();

        $this->em->refresh($file2);

        $filename = substr($file2->getFilePath(), strrpos($file2->getFilePath(), '/') + 1);

        $this->assertEquals('test-2.txt', $filename);
    }

    /**
     * @expectedException Gedmo\Exception\UploadableUploadException
     */
    public function test_moveFile_ifUploadedFileCantBeMovedThrowException()
    {
        $this->listener->returnFalseOnMoveUploadedFile = true;

        $file = new Image();
        $file->setTitle('test');
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();
    }

    /**
     * @expectedException RuntimeException
     */
    public function test_addEntityFileInfo_ifFileInfoIsNotValidThrowException()
    {
        $this->listener->addEntityFileInfo(new Image, 'invalidFileInfo');
    }

    /**
     * @expectedException RuntimeException
     */
    public function test_getEntityFileInfo_ifTheresNoFileInfoForEntityThrowException()
    {
        $this->listener->getEntityFileInfo(new Image);
    }

    /**
     * @expectedException Gedmo\Exception\UploadableMaxSizeException
     */
    public function test_fileExceedingMaximumAllowedSizeThrowsException()
    {
        // We set the default path on the listener
        $this->listener->setDefaultPath($this->destinationTestDir);

        $file = new FileWithMaxSize();
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();
    }

    public function test_fileNotExceedingMaximumAllowedSizeDoesntThrowException()
    {
        // We set the default path on the listener
        $this->listener->setDefaultPath($this->destinationTestDir);

        $file = new FileWithMaxSize();
        $size = 0.0001;
        $fileInfo = $this->generateUploadedFile('image', false, false, array('size' => $size));

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $this->em->refresh($file);

        $this->assertEquals($size, $file->getFileSize());
    }

    /**
     * @expectedException Gedmo\Exception\UploadableCouldntGuessMimeTypeException
     */
    public function test_ifMimeTypeGuesserCantResolveTypeThrowException()
    {
        // We set the default path on the listener
        $this->listener->setDefaultPath($this->destinationTestDir);
        $this->listener->setMimeTypeGuesser(new MimeTypeGuesserStub(null));

        $file = new FileWithAllowedTypes();
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();
    }

    /**
     * @expectedException Gedmo\Exception\UploadableInvalidMimeTypeException
     */
    public function test_allowedTypesOption_ifMimeTypeIsInvalidThrowException()
    {
        // We set the default path on the listener
        $this->listener->setDefaultPath($this->destinationTestDir);
        $this->listener->setMimeTypeGuesser(new MimeTypeGuesserStub('text/css'));

        $file = new FileWithAllowedTypes();
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();
    }

    public function test_allowedTypesOption_ifMimeTypeIsValidThenDontThrowException()
    {
        // We set the default path on the listener
        $this->listener->setDefaultPath($this->destinationTestDir);

        $file = new FileWithAllowedTypes();
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();
    }

    /**
     * @expectedException Gedmo\Exception\UploadableInvalidMimeTypeException
     */
    public function test_disallowedTypesOption_ifMimeTypeIsInvalidThrowException()
    {
        // We set the default path on the listener
        $this->listener->setDefaultPath($this->destinationTestDir);
        $this->listener->setMimeTypeGuesser(new MimeTypeGuesserStub('text/css'));

        $file = new FileWithDisallowedTypes();
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();
    }

    public function test_disallowedTypesOption_ifMimeTypeIsValidThenDontThrowException()
    {
        // We set the default path on the listener
        $this->listener->setDefaultPath($this->destinationTestDir);
        $this->listener->setMimeTypeGuesser(new MimeTypeGuesserStub('video/jpeg'));

        $file = new FileWithDisallowedTypes();
        $fileInfo = $this->generateUploadedFile();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();
    }

    /**
     * @expectedException Gedmo\Exception\InvalidArgumentException
     * @dataProvider invalidFileInfoClassesProvider
     */
    public function test_setDefaultFileInfoClass_throwExceptionIfInvalidClassArePassed($class)
    {
        $this->listener->setDefaultFileInfoClass($class);
    }

    public function test_setDefaultFileInfoClass_setClassIfClassIsValid()
    {
        $validClass = 'Gedmo\\Uploadable\\FileInfo\\FileInfoArray';

        $this->listener->setDefaultFileInfoClass($validClass);

        $this->assertEquals($validClass, $this->listener->getDefaultFileInfoClass());
    }

    public function test_useGeneratedFilenameWhenAppendingNumbers()
    {
        // We set the default path on the listener
        $this->listener->setDefaultPath($this->destinationTestDir);

        $file = new FileWithAlphanumericName();
        $fileInfo = $this->generateUploadedFile('file', $this->testFileWithSpaces, $this->testFilenameWithSpaces);

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $filePath = $file->getPath().'/'.str_replace(' ', '-', $fileInfo['name']);

        $this->assertPathEquals($filePath, $file->getFilePath());

        $file = new FileWithAlphanumericName();

        $this->listener->addEntityFileInfo($file, $fileInfo);

        $this->em->persist($file);
        $this->em->flush();

        $filePath = $file->getPath().'/'.str_replace(' ', '-', str_replace('.txt', '-2.txt', $fileInfo['name']));

        $this->assertPathEquals($filePath, $file->getFilePath());
    }

    // Data Providers
    public function invalidFileInfoClassesProvider()
    {
        return array(
            array(''),
            array(false),
            array(null),
            array('FakeFileInfo'),
            array(array()),
            array(new \DateTime())
        );
    }

    public function uploadExceptionsProvider()
    {
        return array(
            array(1, 'Gedmo\Exception\UploadableIniSizeException'),
            array(2, 'Gedmo\Exception\UploadableFormSizeException'),
            array(3, 'Gedmo\Exception\UploadablePartialException'),
            array(4, 'Gedmo\Exception\UploadableNoFileException'),
            array(6, 'Gedmo\Exception\UploadableNoTmpDirException'),
            array(7, 'Gedmo\Exception\UploadableCantWriteException'),
            array(8, 'Gedmo\Exception\UploadableExtensionException'),
            array(999, 'Gedmo\Exception\UploadableUploadException')
        );
    }




    // Util

    private function generateUploadedFile($index = 'image', $filePath = false, $filename = false, array $info = array())
    {
        $defaultInfo = array(
            'tmp_name'          => !$filePath ? $this->testFile : $filePath,
            'name'              => !$filename ? $this->testFilename : $filename,
            'size'              => $this->testFileSize,
            'type'              => $this->testFileMimeType,
            'error'             => 0
        );

        $info = array_merge($defaultInfo, $info);

        return $info;
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::IMAGE_CLASS,
            self::ARTICLE_CLASS,
            self::FILE_CLASS,
            self::FILE_WITHOUT_PATH_CLASS,
            self::FILE_APPEND_NUMBER_CLASS,
            self::FILE_WITH_ALPHANUMERIC_NAME_CLASS,
            self::FILE_WITH_SHA1_NAME_CLASS,
            self::FILE_WITH_CUSTOM_FILENAME_GENERATOR_CLASS,
            self::FILE_WITH_MAX_SIZE_CLASS,
            self::FILE_WITH_ALLOWED_TYPES_CLASS,
            self::FILE_WITH_DISALLOWED_TYPES_CLASS
        );
    }

    private function clearFilesAndDirectories()
    {
        if (is_dir($this->destinationTestDir)) {
            $iter = new \DirectoryIterator($this->destinationTestDir);

            foreach ($iter as $fileInfo) {
                if (!$fileInfo->isDot()) {
                    @unlink($fileInfo->getPathname());
                }
            }
        }
    }

    protected function assertPathEquals($expected, $path, $message = '')
    {
        $this->assertEquals($expected, $path, $message);
    }
}

class FakeFileInfo
{
}

class FakeFilenameGenerator implements \Gedmo\Uploadable\FilenameGenerator\FilenameGeneratorInterface
{
    public static function generate($filename, $extension, $object = null)
    {
        return '123.txt';
    }
}

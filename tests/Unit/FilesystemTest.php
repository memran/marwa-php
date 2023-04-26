<?php


namespace Unit;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToWriteFile;
use Marwa\Application\Filesystems\AdapterDirector;
use Marwa\Application\Filesystems\Adapters\AdapterInterface;
use Marwa\Application\Filesystems\Exceptions\FilesystemException;
use Marwa\Application\Filesystems\FactoryAdapter;
use Marwa\Application\Filesystems\FilesystemInterface;
use Marwa\Application\Filesystems\Filesystem;
use Marwa\Application\Filesystems\Exceptions\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FilesystemTest extends TestCase
{
    /**
     * @throws \Marwa\Application\Exceptions\InvalidArgumentException
     */
    public function testFilesystemConstructorException()
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Filesystem([]);
    }

    /**
     *
     */
    public function testFSInterface()
    {
        $config = new Filesystem([
            'local' => [
                'path'=> '/var/www/html'
            ]
        ]);
        $this->assertInstanceOf(FilesystemInterface::class,$config);
    }

    /**
     * @throws FilesystemException
     * @throws InvalidArgumentException
     */
    public function testFSDisk()
    {
        $config = new Filesystem([
            'local' => [
                'path'=> '/var/www/html'
            ]
        ]);
        $config->disk('local');
        $this->assertEquals('local',$config->getDisk());
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testFSExceptionOnSetEmptyDisk()
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Filesystem([
            'local' => [
                'path'=> '/var/www/html'
            ]
        ]);
        $config->disk('');

    }

    /**
     * @throws FilesystemException
     */
    public function testFSOnGetDiskWithoutSetDisk()
    {
        $config = new Filesystem([
            'local' => [
                'path'=> '/var/www/html'
            ]
        ]);

        $this->assertEquals('local',$config->getDisk());
    }

    /**
     *
     */
    public function testFactoryAdapterInterface()
    {
        $adapter = FactoryAdapter::create('local');
        $this->assertInstanceOf(AdapterInterface::class,$adapter);
    }

    public function testAdapterDirectorException()
    {
        $this->expectException(InvalidArgumentException::class);
        $adapter = FactoryAdapter::create('local');
        $director = new AdapterDirector($adapter,[]);

    }

    public function testAdapterDirectorInterface()
    {
        $config = [
            'local' => [
                'path' => '/var/www/html',
                'visibility'=> [
                    'file' => [
                        'public' => 0640,
                        'private' => 0604,
                    ],
                    'dir' => [
                        'public' => 0740,
                        'private' => 7604,
                    ]
                    ]
                ]
            ];
        $disk = 'local';
        $adapter = FactoryAdapter::create($disk);
        $director = new AdapterDirector($adapter,$config[$disk]);
        $this->assertInstanceOf(AdapterInterface::class,$adapter);
    }
    public function testAdapterDirectorGetAdapter()
    {
        $config = [
            'local' => [
                'path' => '/var/www/html',
                'visibility'=> [
                    'file' => [
                        'public' => 0640,
                        'private' => 0604,
                    ],
                    'dir' => [
                        'public' => 0740,
                        'private' => 7604,
                    ]
                ]
            ]
        ];
        $disk = 'local';
        $adapter = FactoryAdapter::create($disk);
        $director = new AdapterDirector($adapter,$config[$disk]);
        $fs_adapter = $director->build()->getAdapter();
        $this->assertInstanceOf(FilesystemAdapter::class,$fs_adapter);
    }

    /**
     * @throws FilesystemException
     */
    public function testFilesystemInstance()
    {
        $config = new Filesystem([
            'local' => [
                'path'=> '/var/www/html'
            ]
        ]);

        $config->createFilesystem();
        $this->assertInstanceOf(FilesystemOperator::class,$config->getFilesystem());
    }

    /**
     *
     */
    public function testCreateFileException()
    {
        $this->expectException(UnableToWriteFile::class);
        $config = new Filesystem([
            'local' => [
                'path'=> '/var/www/html'
            ]
        ]);
        $fs = $config->getFilesystem();
        $content = '<h1>Hello World</h1>';
        $fs->write('index.html',$content);
    }
}
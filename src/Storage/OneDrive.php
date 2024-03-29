<?php


namespace As247\CloudStorages\Storage;

use As247\CloudStorages\Exception\FileNotFoundException;
use As247\CloudStorages\Exception\StorageException;
use As247\CloudStorages\Support\Config;
use As247\CloudStorages\Support\FileAttributes;
use Generator;
use GuzzleHttp\Exception\ClientException;
use Microsoft\Graph\Exception\GraphException;
use As247\CloudStorages\Service\OneDrive as OneDriveService;
use Microsoft\Graph\Graph;
use Throwable;
use Traversable;

class OneDrive extends Storage
{
	/** @var Graph */
	protected $service;

	public function __construct(Graph $graph,$options=[])
	{
		$this->service = new OneDriveService($graph,$options);
		$this->setLogger($this->service->getLogger());
		$this->setupCache($options);
	}

	public function getService()
	{
		return $this->service;
	}
    public function temporaryUrl(string $path, \DateTimeInterface $expiresAt, Config $config = null): string
    {
        return $this->getMetadata($path)['@downloadUrl']??'';
    }

    /**
	 * @param string $directory
	 * @param bool $recursive
	 * @return Generator
	 * @throws GraphException
	 */
	public function listContents(string $directory = '', bool $recursive = false): Traversable
	{
		try {
			$results = $this->service->listChildren($directory);
			foreach ($results as $id => $result) {
				$result = $this->service->normalizeMetadata($result, rtrim($directory,'\/') . '/' . $result['name']);
				yield $id => $result;
				if ($recursive && $result['type'] === 'dir') {
					yield from $this->listContents($result['path'], $recursive);
				}
			}
		} catch (ClientException $e) {
			if ($e->getResponse()->getStatusCode() === 404) {
				yield from [];
			}
		}
	}

	public function writeStream(string $path, $contents, Config $config = null): void
	{
		try {
			$this->service->upload($path, $contents);
			$this->cache->forgetBranch($path);
			if ($config && $visibility = $config->get('visibility')) {
				$this->setVisibility($path, $visibility);
			}
		} catch (ClientException $e) {
            throw new StorageException($e->getMessage(),'writeStream',$e);
		} catch (GraphException $e) {
            throw new StorageException($e->getMessage(),'writeStream',$e);
		}
	}

	public function readStream(string $path)
	{
		try {
			return $this->service->download($path);
		} catch (ClientException $e) {
            throw new StorageException($e->getMessage(),'readStream',$e);
		} catch (GraphException $e) {
            throw new StorageException($e->getMessage(),'readStream',$e);
		}
	}

	public function delete(string $path): void
	{
		try {
			$this->service->delete($path);
			$this->cache->delete($path);
		} catch (ClientException $e) {
			if ($e->getResponse()->getStatusCode() === 404) {
				throw FileNotFoundException::create($path);
			}
            throw new StorageException($e->getMessage(),'delete',$e);
		} catch (GraphException $e) {
            throw new StorageException($e->getMessage(),'delete',$e);
		}
	}

	public function deleteDirectory(string $path): void
	{
        $this->delete($path);
        $this->cache->deleteDir($path);
	}

	public function createDirectory(string $path, Config $config = null): void
	{
		try {
			$response = $this->service->createDirectory($path);
			$this->cache->forgetBranch($path);

			$file = FileAttributes::fromArray($this->service->normalizeMetadata($response, $path));
			if (!$file->isDir()) {
                throw new StorageException('File already exists at '.$path,'createDirectory');
			}
		} catch (GraphException $e) {
            throw new StorageException($e->getMessage(),'createDirectory');
		} catch (ClientException $e) {
            throw new StorageException($e->getMessage(),'createDirectory');
		}
	}

	/**
	 * @param string $path
	 * @param mixed $visibility
	 * @throws GraphException
	 */
	public function setVisibility(string $path, $visibility): void
	{
		if ($visibility === Storage::VISIBILITY_PUBLIC) {
			$this->service->publish($path);
			$this->cache->forget($path);
		} elseif ($visibility === Storage::VISIBILITY_PRIVATE) {
			$this->service->unPublish($path);
			$this->cache->forget($path);
		} else {
			throw new \InvalidArgumentException('Unknown visibility: ' . $visibility);
		}
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param Config|null $config
	 * @throws GraphException
	 */
	public function move(string $source, string $destination, Config $config = null): void
	{
		$this->service->move($source, $destination);
		$this->cache->move($source,$destination);
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param Config|null $config
	 * @throws GraphException
	 */
	public function copy(string $source, string $destination, Config $config = null): void
	{
		$this->service->copy($source, $destination);
		$this->cache->forgetBranch($destination);
        $this->setVisibility($destination, $this->getMetadata($source)->visibility());
	}


	/**
	 * @param $path
	 * @return FileAttributes
	 * @throws FileNotFoundException
	 */
	public function getMetadata($path): FileAttributes
	{
		try {
			$meta=$this->cache->get($path);
			if(!is_null($meta) && !$meta){
				throw new FileNotFoundException($path);
			}
			if(!isset($meta)) {
				$meta = $this->service->getItem($path, ['expand' => 'permissions']);
				$this->cache->put($path,$meta);
			}
			$attributes = $this->service->normalizeMetadata($meta, $path);
			return FileAttributes::fromArray($attributes);
		} catch (ClientException $e) {
			if ($e->getResponse()->getStatusCode() === 404) {
				$this->cache->put($path,false);
				throw new FileNotFoundException($path, 0, $e);
			}
			throw new StorageException($e->getMessage(),'getMetadata',$e);
		} catch (FileNotFoundException $e){
			throw $e;
		}catch (Throwable $e) {
			throw new StorageException($e->getMessage(),'getMetadata',$e);
		}
	}


}

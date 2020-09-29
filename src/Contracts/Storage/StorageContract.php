<?php


namespace As247\CloudStorages\Contracts\Storage;


use As247\CloudStorages\Exception\FileNotFoundException;
use As247\CloudStorages\Exception\InvalidVisibilityProvided;
use As247\CloudStorages\Exception\UnableToCopyFile;
use As247\CloudStorages\Exception\UnableToCreateDirectory;
use As247\CloudStorages\Exception\UnableToDeleteDirectory;
use As247\CloudStorages\Exception\UnableToDeleteFile;
use As247\CloudStorages\Exception\UnableToMoveFile;
use As247\CloudStorages\Exception\UnableToReadFile;
use As247\CloudStorages\Exception\UnableToRetrieveMetadata;
use As247\CloudStorages\Exception\UnableToWriteFile;
use As247\CloudStorages\Service\GoogleDrive;
use As247\CloudStorages\Service\OneDrive;
use As247\CloudStorages\Support\FileAttributes;
use As247\CloudStorages\Support\StorageAttributes;
use As247\CloudStorages\Support\Config;
use As247\CloudStorages\Exception\FilesystemException;
use Psr\Http\Message\StreamInterface;

interface StorageContract
{

	/**
	 * @const  VISIBILITY_PUBLIC  public visibility
	 */
	const VISIBILITY_PUBLIC = 'public';

	/**
	 * @const  VISIBILITY_PRIVATE  private visibility
	 */
	const VISIBILITY_PRIVATE = 'private';

	/**
	 * @return mixed | GoogleDrive | OneDrive
	 */
	public function getService();
	/**
	 * @param string $path
	 * @param resource|StreamInterface $contents
	 * @param Config $config
	 * @throws UnableToWriteFile
	 * @throws FilesystemException
	 */
	public function writeStream(string $path, $contents, Config $config): void;

	/**
	 * @param string $path
	 * @return resource
	 * @throws UnableToReadFile
	 * @throws FilesystemException
	 */
	public function readStream(string $path);

	/**
	 * @param string $path
	 * @throws UnableToDeleteFile
	 * @throws FilesystemException
	 * @throws FileNotFoundException
	 */
	public function delete(string $path): void;

	/**
	 * @param string $path
	 * @throws UnableToDeleteDirectory
	 * @throws FilesystemException
	 * @throws FileNotFoundException
	 */
	public function deleteDirectory(string $path): void;

	/**
	 * @param string $path
	 * @param Config $config
	 * @throws UnableToCreateDirectory
	 * @throws FilesystemException
	 */
	public function createDirectory(string $path, Config $config): void;

	/**
	 * @param string $path
	 * @param mixed $visibility
	 * @throws InvalidVisibilityProvided
	 * @throws FilesystemException
	 */
	public function setVisibility(string $path, $visibility): void;

	/**
	 * @param string $path
	 * @param bool $deep
	 * @return iterable<StorageAttributes>
	 * @throws FilesystemException
	 */
	public function listContents(string $path, bool $deep): iterable;

	/**
	 * @param string $source
	 * @param string $destination
	 * @param Config $config
	 * @throws UnableToMoveFile
	 * @throws FilesystemException
	 */
	public function move(string $source, string $destination, Config $config): void;

	/**
	 * @param string $source
	 * @param string $destination
	 * @param Config $config
	 * @throws UnableToCopyFile
	 * @throws FilesystemException
	 */
	public function copy(string $source, string $destination, Config $config): void;

	/**
	 * @param $path
	 * @return FileAttributes
	 * @throws FileNotFoundException
	 * @throws UnableToRetrieveMetadata
	 * @throws FilesystemException
	 */
	public function getMetadata($path): FileAttributes;
}

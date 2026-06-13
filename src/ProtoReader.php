<?php

declare(strict_types=1);

namespace GeoSitePhp;

final class ProtoReader
{
    private $data;
    private $position = 0;
    private $length;

    public function __construct(string $data)
    {
        $this->data = $data;
        $this->length = strlen($data);
    }

    public function isDone(): bool
    {
        return $this->position >= $this->length;
    }

    /**
     * @return array{field: int, wire: int}|null
     */
    public function readTag()
    {
        if ($this->isDone()) {
            return null;
        }

        $tag = $this->readVarint();

        return [
            'field' => $tag >> 3,
            'wire' => $tag & 0x07,
        ];
    }

    public function readVarint(): int
    {
        $shift = 0;
        $result = 0;

        while (!$this->isDone()) {
            $byte = ord($this->data[$this->position]);
            $this->position++;
            $result |= (($byte & 0x7f) << $shift);

            if (($byte & 0x80) === 0) {
                return $result;
            }

            $shift += 7;
            if ($shift > 63) {
                throw new \RuntimeException('Invalid protobuf varint.');
            }
        }

        throw new \RuntimeException('Unexpected end of protobuf varint.');
    }

    public function readLengthDelimited(): string
    {
        $length = $this->readVarint();
        if ($length < 0 || $this->position + $length > $this->length) {
            throw new \RuntimeException('Invalid protobuf length-delimited field.');
        }

        $value = substr($this->data, $this->position, $length);
        $this->position += $length;

        return $value;
    }

    public function skip(int $wire)
    {
        switch ($wire) {
            case 0:
                $this->readVarint();
                return;
            case 1:
                $this->skipBytes(8);
                return;
            case 2:
                $this->readLengthDelimited();
                return;
            case 5:
                $this->skipBytes(4);
                return;
            default:
                throw new \RuntimeException("Unsupported protobuf wire type: {$wire}");
        }
    }

    private function skipBytes(int $length)
    {
        if ($this->position + $length > $this->length) {
            throw new \RuntimeException('Unexpected end of protobuf field.');
        }

        $this->position += $length;
    }
}

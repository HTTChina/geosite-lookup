<?php

declare(strict_types=1);

namespace GeoSitePhp;

final class ProtoReader
{
    private int $position = 0;
    private int $length;

    public function __construct(private readonly string $data)
    {
        $this->length = strlen($data);
    }

    public function isDone(): bool
    {
        return $this->position >= $this->length;
    }

    /**
     * @return array{field: int, wire: int}|null
     */
    public function readTag(): ?array
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

    public function skip(int $wire): void
    {
        match ($wire) {
            0 => $this->readVarint(),
            1 => $this->skipBytes(8),
            2 => $this->readLengthDelimited(),
            5 => $this->skipBytes(4),
            default => throw new \RuntimeException("Unsupported protobuf wire type: {$wire}"),
        };
    }

    private function skipBytes(int $length): void
    {
        if ($this->position + $length > $this->length) {
            throw new \RuntimeException('Unexpected end of protobuf field.');
        }

        $this->position += $length;
    }
}

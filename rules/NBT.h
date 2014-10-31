use pocketmine\utils\Binary;

#include <rules/BinaryIO.h>

#define $nbt->put(data) $nbt->buffer .= data

#define $nbt->getLong() $nbt->endianness === 1 ? Binary::readLong($nbt->get(8)) : Binary::readLLong($nbt->get(8))
#define $nbt->putLong(data) $nbt->buffer .= $nbt->endianness === 1 ? Binary::writeLong(data) : Binary::writeLLong(data)

#define $nbt->getInt() $nbt->endianness === 1 ? Binary::readInt($nbt->get(4)) : Binary::readLInt($nbt->get(4))
#define $nbt->putInt(data) $nbt->buffer .= $nbt->endianness === 1 ? Binary::writeInt(data) : Binary::writeLInt(data)

#define $nbt->getShort() $nbt->endianness === 1 ? Binary::readShort($nbt->get(2)) : Binary::readLShort($nbt->get(2))
#define $nbt->getSignedShort() $nbt->endianness === 1 ? Binary::readSignedShort($nbt->get(2)) : Binary::readSignedLShort($nbt->get(2))
#define $nbt->putShort(data) $nbt->buffer .= $nbt->endianness === 1 ? Binary::writeShort(data) : Binary::writeLShort(data)

#define $nbt->getFloat() $nbt->endianness === 1 ? Binary::readFloat($nbt->get(4)) : Binary::readLFloat($nbt->get(4))
#define $nbt->putFloat(data) $nbt->buffer .= $nbt->endianness === 1 ? Binary::writeFloat(data) : Binary::writeLFloat(data)

#define $nbt->getDouble() $nbt->endianness === 1 ? Binary::readDouble($nbt->get(8)) : Binary::readLDouble($nbt->get(8))
#define $nbt->putDouble(data) $nbt->buffer .= $nbt->endianness === 1 ? Binary::writeDouble(data) : Binary::writeLDouble(data)

#define $nbt->getByte() ord($nbt->get(1))
#define $nbt->putByte(data) $nbt->buffer .= chr(data)

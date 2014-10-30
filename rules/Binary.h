
#define Binary::readTriad(data) unpack("N", "\x00" . data)[1]
#define Binary::writeTriad(data) substr(pack("N", data), 1)

#define Binary::readLTriad(data) unpack("V", data . "\x00")[1]
#define Binary::writeLTriad(data) substr(pack("N", data), 0, -1)

#define Binary::readBool(data) ord(data{0}) === 0 ? false : true
#define Binary::writeBool(data) chr(data === true ? 1 : 0)

#define Binary::readByte(data) ord(data)
#define Binary::writeByte(data) chr(data)

#define Binary::readShort(data) unpack("n", data)[1]
#define Binary::readSignedShort(data) (PHP_INT_SIZE === 8 ? unpack("n", data)[1] << 48 >> 48 : unpack("n", data)[1] << 16 >> 16)
#define Binary::writeShort(data) pack("n", data)

#define Binary::readLShort(data) unpack("v", data)[1]
#define Binary::readSignedLShort(data) (PHP_INT_SIZE === 8 ? unpack("v", data)[1] << 48 >> 48 : unpack("v", data)[1] << 16 >> 16)
#define Binary::writeLShort(data) pack("v", data)

#define Binary::readInt(data) (PHP_INT_SIZE === 8 ? unpack("N", data)[1] << 32 >> 32 : unpack("N", data)[1])
#define Binary::writeInt(data) pack("N", data)

#define Binary::readLInt(data) (PHP_INT_SIZE === 8 ? unpack("V", data)[1] << 32 >> 32 : unpack("V", data)[1])
#define Binary::writeLInt(data) pack("V", data)

#define Binary::readFloat(data) (ENDIANNESS === 0 ? unpack("f", data)[1] : unpack("f", strrev(data))[1])
#define Binary::writeFloat(data) (ENDIANNESS === 0 ? pack("f", data) : strrev(pack("f", data)))

#define Binary::readLFloat(data) (ENDIANNESS === 0 ? unpack("f", strrev(data))[1] : unpack("f", data)[1])
#define Binary::writeLFloat(data) (ENDIANNESS === 0 ? strrev(pack("f", data)) : pack("f", data))

#define Binary::readDouble(data) (ENDIANNESS === 0 ? unpack("d", data)[1] : unpack("d", strrev(data))[1])
#define Binary::writeDouble(data) (ENDIANNESS === 0 ? pack("d", data) : strrev(pack("d", data))

#define Binary::readLDouble(data) (ENDIANNESS === 0 ? unpack("d", strrev(data))[1] : unpack("d", data)[1])
#define Binary::writeLDouble(data) (ENDIANNESS === 0 ? strrev(pack("d", data)) : pack("d", data))

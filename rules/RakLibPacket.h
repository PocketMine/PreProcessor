use raklib\Binary;

#define EncapsulatedPacket::getPacketFromPool() EncapsulatedPacket::$nextPacket >= count(EncapsulatedPacket::$packetPool) ? (EncapsulatedPacket::$packetPool[EncapsulatedPacket::$nextPacket++] = new EncapsulatedPacket) : EncapsulatedPacket::$packetPool[EncapsulatedPacket::$nextPacket++]

#include <rules/BinaryIO.h>
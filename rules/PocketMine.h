
#include <rules/Binary.h>

#define COMPILE 1

#define Level::chunkHash(chunkX, chunkZ) PHP_INT_SIZE === 8 ? (((chunkX) & 0xFFFFFFFF) << 32) | ((chunkZ) & 0xFFFFFFFF) : (chunkX) . ":" . (chunkZ)
#define Level::blockHash(x, y, z) PHP_INT_SIZE === 8 ? (((x) & 0xFFFFFFF) << 35) | (((y) & 0x7f) << 28) | ((z) & 0xFFFFFFF) : (x) . ":" . (y) .":". (z)
#define Level::getXZ(hash, chunkX, chunkZ) if(PHP_INT_SIZE === 8){chunkX = (hash >> 32) << 32 >> 32; chunkZ = (hash & 0xFFFFFFFF) << 32 >> 32;}else{list(chunkX, chunkZ) = explode(":", hash); chunkX = (int) chunkX; chunkZ = (int) chunkZ;}

#include <rules/Binary.h>

#define COMPILE 1

#define Level::chunkHash(chunkX, chunkZ) (chunkX . ":" . chunkZ)
#define Level::getXZ(hash, chunkX, chunkZ) list(chunkX, chunkZ) = explode(":", hash); chunkX = (int) chunkX; chunkZ = (int) chunkZ;
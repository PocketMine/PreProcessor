
#define $this->getChunkEntities(chunkX, chunkZ) (($______chunk = $this->getChunk(chunkX, chunkZ)) !== null ? $______chunk->getEntities() : [])

#define $this->isChunkLoaded(chunkX, chunkZ) (isset($this->chunks[Level::chunkHash(chunkX, chunkZ)]) or $this->provider->isChunkLoaded(chunkX, chunkZ))

#define $this->getBlockIdAt(x, y, z) $this->getChunk(x >> 4, z >> 4, true)->getBlockId(x & 0x0f, y & 0x7f, z & 0x0f)
#define $this->getBlockDataAt(x, y, z) $this->getChunk(x >> 4, z >> 4, true)->getBlockData(x & 0x0f, y & 0x7f, z & 0x0f)

#define $this->getBlockLightAt(x, y, z) $this->getChunk(x >> 4, z >> 4, true)->getBlockLight(x & 0x0f, y & 0x7f, z & 0x0f)
#define $this->setBlockLightAt(x, y, z, level) $this->getChunk(x >> 4, z >> 4, true)->setBlockLight(x & 0x0f, y & 0x7f, z & 0x0f, level & 0x0f)
#define $this->getBlockSkyLightAt(x, y, z) $this->getChunk(x >> 4, z >> 4, true)->getBlockSkyLight(x & 0x0f, y & 0x7f, z & 0x0f)
#define $this->setBlockSkyLightAt(x, y, z, level) $this->getChunk(x >> 4, z >> 4, true)->setBlockSkyLight(x & 0x0f, y & 0x7f, z & 0x0f, level & 0x0f)
## EZAdmin工具

### 快取
```php
# 依赖注入

/**
 * 快取
 * @Inject()
 * @var CacheService
 */
protected $cache;

/**
 * 清除快取
 * @Inject()
 * @var CacheFlushService
 */
protected $cacheFlush;

```

```php
# 配置MongoDb連線
$confName = $this->cache->opMongoDbConfig('gf');
$this->mongoDbClient->setPool($confName)->insert("hyperf_test", [
    'aaa'=>'a',
    'bbb'=>'b',
    'ccc'=>'c'
]);
# 獲取Config
$this->config->get("mongodb.{$confName}");
```

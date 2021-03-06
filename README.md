# Photon PHP Framework

Photon is a Lightweight PHP MVC Framework 💡

How to Ignite?
---

To use `Photon` you only need to add theses lines into your index.php folder and create some folders.

```php
require_once(__DIR__ . "/photon.php");
$photon = new Photon();
$photon->ignite();
```

Really Simple to Use
---

If you want to try `Photon` you can easily download this [example](https://github.com/clintnetwork/photon/tree/master/example).

```php
class Home 
{
    public function index()
    {
        return view();
    }

    public function about()
    {
        Photon::$viewbag = "This is the about page.";
        return view();
    }

    /**
     * @route /privacy-policy
     */
    public function privacy()
    {
        return view();
    }
}
```

Credits
---

Photon is powered by [Clint.Network](https://twitter.com/clint_network).

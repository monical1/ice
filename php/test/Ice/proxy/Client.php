<?
// **********************************************************************
//
// Copyright (c) 2003-2009 ZeroC, Inc. All rights reserved.
//
// This copy of Ice is licensed to you under the terms described in the
// ICE_LICENSE file included in this distribution.
//
// **********************************************************************

error_reporting(E_ALL | E_STRICT);

if(!extension_loaded("ice"))
{
    echo "\nerror: Ice extension is not loaded.\n\n";
    exit(1);
}

require 'Ice.php';
require 'Test.php';

function test($b)
{
    if(!$b)
    {
        $bt = debug_backtrace();
        die("\ntest failed in ".$bt[0]["file"]." line ".$bt[0]["line"]."\n");
    }
}

function allTests($communicator)
{
    echo "testing stringToProxy... ";
    flush();
    $ref = "test:default -p 12010";
    $base = $communicator->stringToProxy($ref);
    test($base != null);

    $b1 = $communicator->stringToProxy("test");
    test($b1->ice_getIdentity()->name == "test" && $b1->ice_getIdentity()->category == "" &&
         $b1->ice_getAdapterId() == "" && $b1->ice_getFacet() == "");
    $b1 = $communicator->stringToProxy("test ");
    test($b1->ice_getIdentity()->name == "test" && $b1->ice_getIdentity()->category == "" &&
         $b1->ice_getFacet() == "");
    $b1 = $communicator->stringToProxy(" test ");
    test($b1->ice_getIdentity()->name == "test" && $b1->ice_getIdentity()->category == "" &&
         $b1->ice_getFacet() == "");
    $b1 = $communicator->stringToProxy(" test");
    test($b1->ice_getIdentity()->name == "test" && $b1->ice_getIdentity()->category == "" &&
         $b1->ice_getFacet() == "");
    $b1 = $communicator->stringToProxy("'test -f facet'");
    test($b1->ice_getIdentity()->name == "test -f facet" && $b1->ice_getIdentity()->category == "" &&
         $b1->ice_getFacet() == "");
    try
    {
        $b1 = $communicator->stringToProxy("\"test -f facet'");
        test(false);
    }
    catch(Ice_ProxyParseException $ex)
    {
    }
    $b1 = $communicator->stringToProxy("\"test -f facet\"");
    test($b1->ice_getIdentity()->name == "test -f facet" && $b1->ice_getIdentity()->category == "" &&
         $b1->ice_getFacet() == "");
    $b1 = $communicator->stringToProxy("\"test -f facet@test\"");
    test($b1->ice_getIdentity()->name == "test -f facet@test" && $b1->ice_getIdentity()->category == "" &&
         $b1->ice_getFacet() == "");
    $b1 = $communicator->stringToProxy("\"test -f facet@test @test\"");
    test($b1->ice_getIdentity()->name == "test -f facet@test @test" && $b1->ice_getIdentity()->category == "" &&
         $b1->ice_getFacet() == "");
    try
    {
        $b1 = $communicator->stringToProxy("test test");
        test(false);
    }
    catch(Ice_ProxyParseException $ex)
    {
    }
    $b1 = $communicator->stringToProxy("test\\040test");
    test($b1->ice_getIdentity()->name == "test test" && $b1->ice_getIdentity()->category == "");
    try
    {
        $b1 = $communicator->stringToProxy("test\\777");
        test(false);
    }
    catch(Ice_IdentityParseException $ex)
    {
    }
    $b1 = $communicator->stringToProxy("test\\40test");
    test($b1->ice_getIdentity()->name == "test test");

    // Test some octal and hex corner cases.
    $b1 = $communicator->stringToProxy("test\\4test");
    test($b1->ice_getIdentity()->name == "test\4test");
    $b1 = $communicator->stringToProxy("test\\04test");
    test($b1->ice_getIdentity()->name == "test\4test");
    $b1 = $communicator->stringToProxy("test\\004test");
    test($b1->ice_getIdentity()->name == "test\4test");
    $b1 = $communicator->stringToProxy("test\\1114test");
    test($b1->ice_getIdentity()->name == "test\1114test");

    $b1 = $communicator->stringToProxy("test\\b\\f\\n\\r\\t\\'\\\"\\\\test");
    test($b1->ice_getIdentity()->name == "test\x08\x0c\n\r\t'\"\\test" && $b1->ice_getIdentity()->category == "");

    $b1 = $communicator->stringToProxy("category/test");
    test($b1->ice_getIdentity()->name == "test" && $b1->ice_getIdentity()->category == "category" &&
         $b1->ice_getAdapterId() == "");

    $b1 = $communicator->stringToProxy("test@adapter");
    test($b1->ice_getIdentity()->name == "test" && $b1->ice_getIdentity()->category == "" &&
         $b1->ice_getAdapterId() == "adapter");
    try
    {
        $b1 = $communicator->stringToProxy("id@adapter test");
        test(false);
    }
    catch(Ice_ProxyParseException $ex)
    {
    }
    $b1 = $communicator->stringToProxy("category/test@adapter");
    test($b1->ice_getIdentity()->name == "test" && $b1->ice_getIdentity()->category == "category" &&
         $b1->ice_getAdapterId() == "adapter");
    $b1 = $communicator->stringToProxy("category/test@adapter:tcp");
    test($b1->ice_getIdentity()->name == "test" && $b1->ice_getIdentity()->category == "category" &&
         $b1->ice_getAdapterId() == "adapter:tcp");
    $b1 = $communicator->stringToProxy("'category 1/test'@adapter");
    test($b1->ice_getIdentity()->name == "test" && $b1->ice_getIdentity()->category == "category 1" &&
         $b1->ice_getAdapterId() == "adapter");
    $b1 = $communicator->stringToProxy("'category/test 1'@adapter");
    test($b1->ice_getIdentity()->name == "test 1" && $b1->ice_getIdentity()->category == "category" &&
         $b1->ice_getAdapterId() == "adapter");
    $b1 = $communicator->stringToProxy("'category/test'@'adapter 1'");
    test($b1->ice_getIdentity()->name == "test" && $b1->ice_getIdentity()->category == "category" &&
         $b1->ice_getAdapterId() == "adapter 1");
    $b1 = $communicator->stringToProxy("\"category \\/test@foo/test\"@adapter");
    test($b1->ice_getIdentity()->name == "test" && $b1->ice_getIdentity()->category == "category /test@foo" &&
         $b1->ice_getAdapterId() == "adapter");
    $b1 = $communicator->stringToProxy("\"category \\/test@foo/test\"@\"adapter:tcp\"");
    test($b1->ice_getIdentity()->name == "test" && $b1->ice_getIdentity()->category == "category /test@foo" &&
         $b1->ice_getAdapterId() == "adapter:tcp");

    $b1 = $communicator->stringToProxy("id -f facet");
    test($b1->ice_getIdentity()->name == "id" && $b1->ice_getIdentity()->category == "" &&
         $b1->ice_getFacet() == "facet");
    $b1 = $communicator->stringToProxy("id -f 'facet x'");
    test($b1->ice_getIdentity()->name == "id" && $b1->ice_getIdentity()->category == "" &&
         $b1->ice_getFacet() == "facet x");
    $b1 = $communicator->stringToProxy("id -f \"facet x\"");
    test($b1->ice_getIdentity()->name == "id" && $b1->ice_getIdentity()->category == "" &&
         $b1->ice_getFacet() == "facet x");
    try
    {
        $b1 = $communicator->stringToProxy("id -f \"facet x");
        test(false);
    }
    catch(Ice_ProxyParseException $ex)
    {
    }
    try
    {
        $b1 = $communicator->stringToProxy("id -f \'facet x");
        test(false);
    }
    catch(Ice_ProxyParseException $ex)
    {
    }
    $b1 = $communicator->stringToProxy("test -f facet:tcp");
    test($b1->ice_getIdentity()->name == "test" && $b1->ice_getIdentity()->category == "" &&
         $b1->ice_getFacet() == "facet" && $b1->ice_getAdapterId() == "");
    $b1 = $communicator->stringToProxy("test -f \"facet:tcp\"");
    test($b1->ice_getIdentity()->name == "test" && $b1->ice_getIdentity()->category == "" &&
         $b1->ice_getFacet() == "facet:tcp" && $b1->ice_getAdapterId() == "");
    $b1 = $communicator->stringToProxy("test -f facet@test");
    test($b1->ice_getIdentity()->name == "test" && $b1->ice_getIdentity()->category == "" &&
         $b1->ice_getFacet() == "facet" && $b1->ice_getAdapterId() == "test");
    $b1 = $communicator->stringToProxy("test -f 'facet@test'");
    test($b1->ice_getIdentity()->name == "test" && $b1->ice_getIdentity()->category == "" &&
         $b1->ice_getFacet() == "facet@test" && $b1->ice_getAdapterId() == "");
    $b1 = $communicator->stringToProxy("test -f 'facet@test'@test");
    test($b1->ice_getIdentity()->name == "test" && $b1->ice_getIdentity()->category == "" &&
         $b1->ice_getFacet() == "facet@test" && $b1->ice_getAdapterId() == "test");
    try
    {
        $b1 = $communicator->stringToProxy("test -f facet@test @test");
        test(false);
    }
    catch(Ice_ProxyParseException $ex)
    {
    }
    $b1 = $communicator->stringToProxy("test");
    test($b1->ice_isTwoway());
    $b1 = $communicator->stringToProxy("test -t");
    test($b1->ice_isTwoway());
    $b1 = $communicator->stringToProxy("test -o");
    test($b1->ice_isOneway());
    $b1 = $communicator->stringToProxy("test -O");
    test($b1->ice_isBatchOneway());
    $b1 = $communicator->stringToProxy("test -d");
    test($b1->ice_isDatagram());
    $b1 = $communicator->stringToProxy("test -D");
    test($b1->ice_isBatchDatagram());
    $b1 = $communicator->stringToProxy("test");
    test(!$b1->ice_isSecure());
    $b1 = $communicator->stringToProxy("test -s");
    test($b1->ice_isSecure());

    try
    {
        $b1 = $communicator->stringToProxy("test:tcp@adapterId");
        test(false);
    }
    catch(Ice_EndpointParseException $ex)
    {
    }
    // This is an unknown endpoint warning, not a parse exception.
    //
    //try
    //{
    //   $b1 = $communicator->stringToProxy("test -f the:facet:tcp");
    //   test(false);
    //}
    //catch(Ice_EndpointParseException $ex)
    //{
    //}
    try
    {
        $b1 = $communicator->stringToProxy("test::tcp");
        test(false);
    }
    catch(Ice_EndpointParseException $ex)
    {
    }
    echo "ok\n";

    echo "testing propertyToProxy... ";
    $propertyPrefix = "Foo.Proxy";
    $communicator->getProperties()->setProperty($propertyPrefix, "test:default -p 12010");
    $b1 = $communicator->propertyToProxy($propertyPrefix);
    test($b1->ice_getIdentity()->name == "test" && $b1->ice_getIdentity()->category == "" &&
         $b1->ice_getAdapterId() == "" && $b1->ice_getFacet() == "");

    // These two properties don't do anything to direct proxies so
    // first we test that.
    //
    // Commented out because setting a locator or locator cache
    // timeout on a direct proxy causes warning.
    //
    // $property = $propertyPrefix . ".Locator";
    // test(!$b1->ice_getLocator());
    // $communicator->getProperties()->setProperty($property, "locator:default -p 10000");
    // $b1 = $communicator->propertyToProxy($propertyPrefix);
    // test(!$b1->ice_getLocator());
    // $communicator->getProperties()->setProperty($property, "");

    // $property = $propertyPrefix . ".LocatorCacheTimeout";
    // test($b1->ice_getLocatorCacheTimeout() == 0);
    // $communicator->getProperties()->setProperty($property, "1");
    // $b1 = $communicator->propertyToProxy($propertyPrefix);
    // test($b1->ice_getLocatorCacheTimeout() == 0);
    // $communicator->getProperties()->setProperty($property, "");

    // Now retest with an indirect proxy.
    $communicator->getProperties()->setProperty($propertyPrefix, "test");
    $property = $propertyPrefix . ".Locator";
    $communicator->getProperties()->setProperty($property, "locator:default -p 10000");
    $b1 = $communicator->propertyToProxy($propertyPrefix);
    test($b1->ice_getLocator() && $b1->ice_getLocator()->ice_getIdentity()->name == "locator");
    $communicator->getProperties()->setProperty($property, "");

    $property = $propertyPrefix . ".LocatorCacheTimeout";
    test($b1->ice_getLocatorCacheTimeout() == -1);
    $communicator->getProperties()->setProperty($property, "1");
    $b1 = $communicator->propertyToProxy($propertyPrefix);
    test($b1->ice_getLocatorCacheTimeout() == 1);
    $communicator->getProperties()->setProperty($property, "");

    // This cannot be tested so easily because the $property is cached
    // on communicator initialization.
    //
    //$communicator->getProperties()->setProperty("Ice.Default.LocatorCacheTimeout", "60");
    //$b1 = $communicator->propertyToProxy($propertyPrefix);
    //test($b1->ice_getLocatorCacheTimeout() == 60);
    //$communicator->getProperties()->setProperty("Ice.Default.LocatorCacheTimeout", "");

    $communicator->getProperties()->setProperty($propertyPrefix, "test:default -p 12010");

    $property = $propertyPrefix . ".Router";
    test(!$b1->ice_getRouter());
    $communicator->getProperties()->setProperty($property, "router:default -p 10000");
    $b1 = $communicator->propertyToProxy($propertyPrefix);
    test($b1->ice_getRouter() && $b1->ice_getRouter()->ice_getIdentity()->name == "router");
    $communicator->getProperties()->setProperty($property, "");

    $property = $propertyPrefix . ".PreferSecure";
    test(!$b1->ice_isPreferSecure());
    $communicator->getProperties()->setProperty($property, "1");
    $b1 = $communicator->propertyToProxy($propertyPrefix);
    test($b1->ice_isPreferSecure());
    $communicator->getProperties()->setProperty($property, "");

    $property = $propertyPrefix . ".ConnectionCached";
    test($b1->ice_isConnectionCached());
    $communicator->getProperties()->setProperty($property, "0");
    $b1 = $communicator->propertyToProxy($propertyPrefix);
    test(!$b1->ice_isConnectionCached());
    $communicator->getProperties()->setProperty($property, "");

    $property = $propertyPrefix . ".EndpointSelection";
    test($b1->ice_getEndpointSelection() == Ice_EndpointSelectionType::Random);
    $communicator->getProperties()->setProperty($property, "Random");
    $b1 = $communicator->propertyToProxy($propertyPrefix);
    test($b1->ice_getEndpointSelection() == Ice_EndpointSelectionType::Random);
    $communicator->getProperties()->setProperty($property, "Ordered");
    $b1 = $communicator->propertyToProxy($propertyPrefix);
    test($b1->ice_getEndpointSelection() == Ice_EndpointSelectionType::Ordered);
    $communicator->getProperties()->setProperty($property, "");

    //$property = $propertyPrefix . ".CollocationOptimized";
    //test($b1->ice_isCollocationOptimized());
    //$communicator->getProperties()->setProperty($property, "0");
    //$b1 = $communicator->propertyToProxy($propertyPrefix);
    //test(!$b1->ice_isCollocationOptimized());
    //$communicator->getProperties()->setProperty($property, "");

    echo "ok\n";

    echo "testing proxy methods... ";
    flush();
    test($communicator->identityToString($base->ice_identity($communicator->stringToIdentity("other"))->ice_getIdentity()) == "other");
    test($base->ice_facet("facet")->ice_getFacet() == "facet");
    test($base->ice_adapterId("id")->ice_getAdapterId() == "id");
    test($base->ice_twoway()->ice_isTwoway());
    test($base->ice_oneway()->ice_isOneway());
    test($base->ice_batchOneway()->ice_isBatchOneway());
    test($base->ice_datagram()->ice_isDatagram());
    test($base->ice_batchDatagram()->ice_isBatchDatagram());
    test($base->ice_secure(true)->ice_isSecure());
    test(!$base->ice_secure(false)->ice_isSecure());
    echo "ok\n";

    echo "testing ice_getCommunicator... ";
    flush();
    test($base->ice_getCommunicator() === $communicator);
    echo "ok\n";

    echo "testing checked cast... ";
    flush();
    $cl = $base->ice_checkedCast("::Test::MyClass");
    test($cl != null);
    $derived = $cl->ice_checkedCast("::Test::MyDerivedClass");
    test($derived != null);
    test($cl == $base);
    test($derived == $base);
    test($cl == $derived);
    echo "ok\n";

    echo "testing checked cast with context... ";
    flush();
    $c = $cl->getContext();
    test(count($c) == 0);

    $c["one"] = "hello";
    $c["two"] = "world";
    $cl = $base->ice_checkedCast("::Test::MyClass", $c);
    $c2 = $cl->getContext();
    test($c == $c2);

    echo "ok\n";

    echo "testing opaque endpoints... ";

    try
    {
        // Invalid -x option
        $p = $communicator->stringToProxy("id:opaque -t 99 -v abc -x abc");
        test(false);
    }
    catch(Ice_EndpointParseException $ex)
    {
    }

    try
    {
        // Missing -t and -v
        $p = $communicator->stringToProxy("id:opaque");
        test(false);
    }
    catch(Ice_EndpointParseException $ex)
    {
    }

    try
    {
        // Repeated -t
        $p = $communicator->stringToProxy("id:opaque -t 1 -t 1 -v abc");
        test(false);
    }
    catch(Ice_EndpointParseException $ex)
    {
    }

    try
    {
        // Repeated -v
        $p = $communicator->stringToProxy("id:opaque -t 1 -v abc -v abc");
        test(false);
    }
    catch(Ice_EndpointParseException $ex)
    {
    }

    try
    {
        // Missing -t
        $p = $communicator->stringToProxy("id:opaque -v abc");
        test(false);
    }
    catch(Ice_EndpointParseException $ex)
    {
    }

    try
    {
        // Missing -v
        $p = $communicator->stringToProxy("id:opaque -t 1");
        test(false);
    }
    catch(Ice_EndpointParseException $ex)
    {
    }

    try
    {
        // Missing arg for -t
        $p = $communicator->stringToProxy("id:opaque -t -v abc");
        test(false);
    }
    catch(Ice_EndpointParseException $ex)
    {
    }

    try
    {
        // Missing arg for -v
        $p = $communicator->stringToProxy("id:opaque -t 1 -v");
        test(false);
    }
    catch(Ice_EndpointParseException $ex)
    {
    }

    try
    {
        // Not a number for -t
        $p = $communicator->stringToProxy("id:opaque -t x -v abc");
        test(false);
    }
    catch(Ice_EndpointParseException $ex)
    {
    }

    try
    {
        // < 0 for -t
        $p = $communicator->stringToProxy("id:opaque -t -1 -v abc");
        test(false);
    }
    catch(Ice_EndpointParseException $ex)
    {
    }

    try
    {
        // Invalid char for -v
        $p = $communicator->stringToProxy("id:opaque -t 99 -v x?c");
        test(false);
    }
    catch(Ice_EndpointParseException $ex)
    {
    }

    // Legal TCP endpoint expressed as opaque endpoint
    $p1 = $communicator->stringToProxy("test:opaque -t 1 -v CTEyNy4wLjAuMeouAAAQJwAAAA==");
    $pstr = $communicator->proxyToString($p1);
    test($pstr == "test -t:tcp -h 127.0.0.1 -p 12010 -t 10000");
    
    // Working?
    if($communicator->getProperties()->getProperty("Ice.IPv6") == "" ||
       $communicator->getProperties()->getProperty("Ice.IPv6") == "0")
    {
        $ssl = $communicator->getProperties()->getProperty("Ice.Default.Protocol") == "ssl";
        if(!$ssl)
        {
            $p1->ice_ping();
        }

        // Two legal TCP endpoints expressed as opaque endpoints
        $p1 = $communicator->stringToProxy("test:opaque -t 1 -v CTEyNy4wLjAuMeouAAAQJwAAAA==:opaque -t 1 -v CTEyNy4wLjAuMusuAAAQJwAAAA==");
        $pstr = $communicator->proxyToString($p1);
        test($pstr == "test -t:tcp -h 127.0.0.1 -p 12010 -t 10000:tcp -h 127.0.0.2 -p 12011 -t 10000");

        //
        // Test that an SSL endpoint and a nonsense endpoint get written
        // back out as an opaque endpoint.
        //
        $p1 = $communicator->stringToProxy("test:opaque -t 2 -v CTEyNy4wLjAuMREnAAD/////AA==:opaque -t 99 -v abch");
        $pstr = $communicator->proxyToString($p1);
        if(!$ssl)
        {
            test($pstr == "test -t:opaque -t 2 -v CTEyNy4wLjAuMREnAAD/////AA==:opaque -t 99 -v abch");
        }
        else
        {
            test($pstr == "test -t:ssl -h 127.0.0.1 -p 10001:opaque -t 99 -v abch");
        }

        //
        // Try to invoke on the SSL endpoint to verify that we get a
        // NoEndpointException (or ConnectionRefusedException when
        // running with SSL).
        //
        try
        {
            $p1->ice_ping();
            test(false);
        }
        catch(Ice_NoEndpointException $ex)
        {
            test(!$ssl);
        }
        catch(Ice_ConnectionRefusedException $ex)
        {
            test($ssl);
        }

        //
        // Test that the proxy with an SSL endpoint and a nonsense
        // endpoint (which the server doesn't understand either) can be
        // sent over the wire and returned by the server without losing
        // the opaque endpoints.
        //
        $p2 = $derived->_echo($p1);
        $pstr = $communicator->proxyToString($p2);
        if(!$ssl)
        {
            test($pstr == "test -t:opaque -t 2 -v CTEyNy4wLjAuMREnAAD/////AA==:opaque -t 99 -v abch");
        }
        else
        {
            test($pstr == "test -t:ssl -h 127.0.0.1 -p 10001:opaque -t 99 -v abch");
        }
    }
    echo "ok\n";

    return $cl;
}

$communicator = Ice_initialize(&$argv);
$myClass = allTests($communicator);
$myClass->shutdown();
$communicator->destroy();

exit();
?>

<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/***
 *
 * https://www.vmware.com/support/developer/converter-sdk/conv61_apireference/vmodl.query.PropertyCollector.RetrieveResult.html
 */
class RetrieveResult
{
    /** @var ObjectContent[] */
    public $objects;

    /**
     * A token used to retrieve further retrieve results.
     *
     * If set, the token should be passed to ContinueRetrievePropertiesEx to
     * retrieve more results. Each token may be passed to continueRetrievePropertiesEx
     * only once, and only in the same session in which it was returned and to the same
     * PropertyCollector object that returned it.
     *
     * If unset, there are no further results to retrieve after this RetrieveResult.
     *
     * @var string|null
     */
    public $token;

    public function hasMoreResults()
    {
        return $this->token !== null;
    }
}

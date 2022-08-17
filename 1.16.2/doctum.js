var Doctum = {
    treeJson: {"tree":{"l":0,"n":"","p":"","c":[{"l":1,"n":"Google","p":"Google","c":[{"l":2,"n":"ApiCore","p":"Google/ApiCore","c":[{"l":3,"n":"Middleware","p":"Google/ApiCore/Middleware","c":[{"l":4,"n":"CredentialsWrapperMiddleware","p":"Google/ApiCore/Middleware/CredentialsWrapperMiddleware"},{"l":4,"n":"FixedHeaderMiddleware","p":"Google/ApiCore/Middleware/FixedHeaderMiddleware"},{"l":4,"n":"OperationsMiddleware","p":"Google/ApiCore/Middleware/OperationsMiddleware"},{"l":4,"n":"OptionsFilterMiddleware","p":"Google/ApiCore/Middleware/OptionsFilterMiddleware"},{"l":4,"n":"PagedMiddleware","p":"Google/ApiCore/Middleware/PagedMiddleware"},{"l":4,"n":"ResponseMetadataMiddleware","p":"Google/ApiCore/Middleware/ResponseMetadataMiddleware"},{"l":4,"n":"RetryMiddleware","p":"Google/ApiCore/Middleware/RetryMiddleware"}]},{"l":3,"n":"ResourceTemplate","p":"Google/ApiCore/ResourceTemplate","c":[{"l":4,"n":"AbsoluteResourceTemplate","p":"Google/ApiCore/ResourceTemplate/AbsoluteResourceTemplate"},{"l":4,"n":"Parser","p":"Google/ApiCore/ResourceTemplate/Parser"},{"l":4,"n":"RelativeResourceTemplate","p":"Google/ApiCore/ResourceTemplate/RelativeResourceTemplate"},{"l":4,"n":"ResourceTemplateInterface","p":"Google/ApiCore/ResourceTemplate/ResourceTemplateInterface"},{"l":4,"n":"Segment","p":"Google/ApiCore/ResourceTemplate/Segment"}]},{"l":3,"n":"Testing","p":"Google/ApiCore/Testing","c":[{"l":4,"n":"GeneratedTest","p":"Google/ApiCore/Testing/GeneratedTest"},{"l":4,"n":"MessageAwareArrayComparator","p":"Google/ApiCore/Testing/MessageAwareArrayComparator"},{"l":4,"n":"MessageAwareExporter","p":"Google/ApiCore/Testing/MessageAwareExporter"},{"l":4,"n":"MockBidiStreamingCall","p":"Google/ApiCore/Testing/MockBidiStreamingCall"},{"l":4,"n":"MockClientStreamingCall","p":"Google/ApiCore/Testing/MockClientStreamingCall"},{"l":4,"n":"MockGrpcTransport","p":"Google/ApiCore/Testing/MockGrpcTransport"},{"l":4,"n":"MockRequest","p":"Google/ApiCore/Testing/MockRequest"},{"l":4,"n":"MockRequestBody","p":"Google/ApiCore/Testing/MockRequestBody"},{"l":4,"n":"MockResponse","p":"Google/ApiCore/Testing/MockResponse"},{"l":4,"n":"MockServerStreamingCall","p":"Google/ApiCore/Testing/MockServerStreamingCall"},{"l":4,"n":"MockStatus","p":"Google/ApiCore/Testing/MockStatus"},{"l":4,"n":"MockStubTrait","p":"Google/ApiCore/Testing/MockStubTrait"},{"l":4,"n":"MockTransport","p":"Google/ApiCore/Testing/MockTransport"},{"l":4,"n":"MockUnaryCall","p":"Google/ApiCore/Testing/MockUnaryCall"},{"l":4,"n":"ProtobufGPBEmptyComparator","p":"Google/ApiCore/Testing/ProtobufGPBEmptyComparator"},{"l":4,"n":"ProtobufMessageComparator","p":"Google/ApiCore/Testing/ProtobufMessageComparator"},{"l":4,"n":"ReceivedRequest","p":"Google/ApiCore/Testing/ReceivedRequest"},{"l":4,"n":"SerializationTrait","p":"Google/ApiCore/Testing/SerializationTrait"}]},{"l":3,"n":"Transport","p":"Google/ApiCore/Transport","c":[{"l":4,"n":"Grpc","p":"Google/ApiCore/Transport/Grpc","c":[{"l":5,"n":"ForwardingCall","p":"Google/ApiCore/Transport/Grpc/ForwardingCall"},{"l":5,"n":"ForwardingServerStreamingCall","p":"Google/ApiCore/Transport/Grpc/ForwardingServerStreamingCall"},{"l":5,"n":"ForwardingUnaryCall","p":"Google/ApiCore/Transport/Grpc/ForwardingUnaryCall"},{"l":5,"n":"ServerStreamingCallWrapper","p":"Google/ApiCore/Transport/Grpc/ServerStreamingCallWrapper"},{"l":5,"n":"UnaryInterceptorInterface","p":"Google/ApiCore/Transport/Grpc/UnaryInterceptorInterface"}]},{"l":4,"n":"Rest","p":"Google/ApiCore/Transport/Rest","c":[{"l":5,"n":"JsonStreamDecoder","p":"Google/ApiCore/Transport/Rest/JsonStreamDecoder"},{"l":5,"n":"RestServerStreamingCall","p":"Google/ApiCore/Transport/Rest/RestServerStreamingCall"}]},{"l":4,"n":"GrpcFallbackTransport","p":"Google/ApiCore/Transport/GrpcFallbackTransport"},{"l":4,"n":"GrpcTransport","p":"Google/ApiCore/Transport/GrpcTransport"},{"l":4,"n":"HttpUnaryTransportTrait","p":"Google/ApiCore/Transport/HttpUnaryTransportTrait"},{"l":4,"n":"RestTransport","p":"Google/ApiCore/Transport/RestTransport"},{"l":4,"n":"TransportInterface","p":"Google/ApiCore/Transport/TransportInterface"}]},{"l":3,"n":"AgentHeader","p":"Google/ApiCore/AgentHeader"},{"l":3,"n":"ApiException","p":"Google/ApiCore/ApiException"},{"l":3,"n":"ApiStatus","p":"Google/ApiCore/ApiStatus"},{"l":3,"n":"ArrayTrait","p":"Google/ApiCore/ArrayTrait"},{"l":3,"n":"BidiStream","p":"Google/ApiCore/BidiStream"},{"l":3,"n":"Call","p":"Google/ApiCore/Call"},{"l":3,"n":"ClientStream","p":"Google/ApiCore/ClientStream"},{"l":3,"n":"CredentialsWrapper","p":"Google/ApiCore/CredentialsWrapper"},{"l":3,"n":"FixedSizeCollection","p":"Google/ApiCore/FixedSizeCollection"},{"l":3,"n":"GPBLabel","p":"Google/ApiCore/GPBLabel"},{"l":3,"n":"GPBType","p":"Google/ApiCore/GPBType"},{"l":3,"n":"GapicClientTrait","p":"Google/ApiCore/GapicClientTrait"},{"l":3,"n":"GrpcSupportTrait","p":"Google/ApiCore/GrpcSupportTrait"},{"l":3,"n":"OperationResponse","p":"Google/ApiCore/OperationResponse"},{"l":3,"n":"Page","p":"Google/ApiCore/Page"},{"l":3,"n":"PageStreamingDescriptor","p":"Google/ApiCore/PageStreamingDescriptor"},{"l":3,"n":"PagedListResponse","p":"Google/ApiCore/PagedListResponse"},{"l":3,"n":"PathTemplate","p":"Google/ApiCore/PathTemplate"},{"l":3,"n":"PollingTrait","p":"Google/ApiCore/PollingTrait"},{"l":3,"n":"RequestBuilder","p":"Google/ApiCore/RequestBuilder"},{"l":3,"n":"RequestParamsHeaderDescriptor","p":"Google/ApiCore/RequestParamsHeaderDescriptor"},{"l":3,"n":"RetrySettings","p":"Google/ApiCore/RetrySettings"},{"l":3,"n":"Serializer","p":"Google/ApiCore/Serializer"},{"l":3,"n":"ServerStream","p":"Google/ApiCore/ServerStream"},{"l":3,"n":"ServerStreamingCallInterface","p":"Google/ApiCore/ServerStreamingCallInterface"},{"l":3,"n":"ServiceAddressTrait","p":"Google/ApiCore/ServiceAddressTrait"},{"l":3,"n":"UriTrait","p":"Google/ApiCore/UriTrait"},{"l":3,"n":"ValidationException","p":"Google/ApiCore/ValidationException"},{"l":3,"n":"ValidationTrait","p":"Google/ApiCore/ValidationTrait"},{"l":3,"n":"Version","p":"Google/ApiCore/Version"}]}]}]},"treeOpenLevel":1},
    /** @var boolean */
    treeLoaded: false,
    /** @var boolean */
    listenersRegistered: false,
    autoCompleteData: null,
    /** @var boolean */
    autoCompleteLoading: false,
    /** @var boolean */
    autoCompleteLoaded: false,
    /** @var string|null */
    rootPath: null,
    /** @var string|null */
    autoCompleteDataUrl: null,
    /** @var HTMLElement|null */
    doctumSearchAutoComplete: null,
    /** @var HTMLElement|null */
    doctumSearchAutoCompleteProgressBarContainer: null,
    /** @var HTMLElement|null */
    doctumSearchAutoCompleteProgressBar: null,
    /** @var number */
    doctumSearchAutoCompleteProgressBarPercent: 0,
    /** @var autoComplete|null */
    autoCompleteJS: null,
    querySearchSecurityRegex: /([^0-9a-zA-Z:\\\\_\s])/gi,
    buildTreeNode: function (treeNode, htmlNode, treeOpenLevel) {
        var ulNode = document.createElement('ul');
        for (var childKey in treeNode.c) {
            var child = treeNode.c[childKey];
            var liClass = document.createElement('li');
            var hasChildren = child.hasOwnProperty('c');
            var nodeSpecialName = (hasChildren ? 'namespace:' : 'class:') + child.p.replace(/\//g, '_');
            liClass.setAttribute('data-name', nodeSpecialName);

            // Create the node that will have the text
            var divHd = document.createElement('div');
            var levelCss = child.l - 1;
            divHd.className = hasChildren ? 'hd' : 'hd leaf';
            divHd.style.paddingLeft = (hasChildren ? (levelCss * 18) : (8 + (levelCss * 18))) + 'px';
            if (hasChildren) {
                if (child.l <= treeOpenLevel) {
                    liClass.className = 'opened';
                }
                var spanIcon = document.createElement('span');
                spanIcon.className = 'icon icon-play';
                divHd.appendChild(spanIcon);
            }
            var aLink = document.createElement('a');

            // Edit the HTML link to work correctly based on the current depth
            aLink.href = Doctum.rootPath + child.p + '.html';
            aLink.innerText = child.n;
            divHd.appendChild(aLink);
            liClass.appendChild(divHd);

            // It has children
            if (hasChildren) {
                var divBd = document.createElement('div');
                divBd.className = 'bd';
                Doctum.buildTreeNode(child, divBd, treeOpenLevel);
                liClass.appendChild(divBd);
            }
            ulNode.appendChild(liClass);
        }
        htmlNode.appendChild(ulNode);
    },
    initListeners: function () {
        if (Doctum.listenersRegistered) {
            // Quick exit, already registered
            return;
        }
                Doctum.listenersRegistered = true;
    },
    loadTree: function () {
        if (Doctum.treeLoaded) {
            // Quick exit, already registered
            return;
        }
        Doctum.rootPath = document.body.getAttribute('data-root-path');
        Doctum.buildTreeNode(Doctum.treeJson.tree, document.getElementById('api-tree'), Doctum.treeJson.treeOpenLevel);

        // Toggle left-nav divs on click
        $('#api-tree .hd span').on('click', function () {
            $(this).parent().parent().toggleClass('opened');
        });

        // Expand the parent namespaces of the current page.
        var expected = $('body').attr('data-name');

        if (expected) {
            // Open the currently selected node and its parents.
            var container = $('#api-tree');
            var node = $('#api-tree li[data-name="' + expected + '"]');
            // Node might not be found when simulating namespaces
            if (node.length > 0) {
                node.addClass('active').addClass('opened');
                node.parents('li').addClass('opened');
                var scrollPos = node.offset().top - container.offset().top + container.scrollTop();
                // Position the item nearer to the top of the screen.
                scrollPos -= 200;
                container.scrollTop(scrollPos);
            }
        }
        Doctum.treeLoaded = true;
    },
    pagePartiallyLoaded: function (event) {
        Doctum.initListeners();
        Doctum.loadTree();
        Doctum.loadAutoComplete();
    },
    pageFullyLoaded: function (event) {
        // it may not have received DOMContentLoaded event
        Doctum.initListeners();
        Doctum.loadTree();
        Doctum.loadAutoComplete();
        // Fire the event in the search page too
        if (typeof DoctumSearch === 'object') {
            DoctumSearch.pageFullyLoaded();
        }
    },
    loadAutoComplete: function () {
        if (Doctum.autoCompleteLoaded) {
            // Quick exit, already loaded
            return;
        }
        Doctum.autoCompleteDataUrl = document.body.getAttribute('data-search-index-url');
        Doctum.doctumSearchAutoComplete = document.getElementById('doctum-search-auto-complete');
        Doctum.doctumSearchAutoCompleteProgressBarContainer = document.getElementById('search-progress-bar-container');
        Doctum.doctumSearchAutoCompleteProgressBar = document.getElementById('search-progress-bar');
        if (Doctum.doctumSearchAutoComplete !== null) {
            // Wait for it to be loaded
            Doctum.doctumSearchAutoComplete.addEventListener('init', function (_) {
                Doctum.autoCompleteLoaded = true;
                Doctum.doctumSearchAutoComplete.addEventListener('selection', function (event) {
                    // Go to selection page
                    window.location = Doctum.rootPath + event.detail.selection.value.p;
                });
                Doctum.doctumSearchAutoComplete.addEventListener('navigate', function (event) {
                    // Set selection in text box
                    if (typeof event.detail.selection.value === 'object') {
                        Doctum.doctumSearchAutoComplete.value = event.detail.selection.value.n;
                    }
                });
                Doctum.doctumSearchAutoComplete.addEventListener('results', function (event) {
                    Doctum.markProgressFinished();
                });
            });
        }
        // Check if the lib is loaded
        if (typeof autoComplete === 'function') {
            Doctum.bootAutoComplete();
        }
    },
    markInProgress: function () {
            Doctum.doctumSearchAutoCompleteProgressBarContainer.className = 'search-bar';
            Doctum.doctumSearchAutoCompleteProgressBar.className = 'progress-bar indeterminate';
            if (typeof DoctumSearch === 'object' && DoctumSearch.pageFullyLoaded) {
                DoctumSearch.doctumSearchPageAutoCompleteProgressBarContainer.className = 'search-bar';
                DoctumSearch.doctumSearchPageAutoCompleteProgressBar.className = 'progress-bar indeterminate';
            }
    },
    markProgressFinished: function () {
        Doctum.doctumSearchAutoCompleteProgressBarContainer.className = 'search-bar hidden';
        Doctum.doctumSearchAutoCompleteProgressBar.className = 'progress-bar';
        if (typeof DoctumSearch === 'object' && DoctumSearch.pageFullyLoaded) {
            DoctumSearch.doctumSearchPageAutoCompleteProgressBarContainer.className = 'search-bar hidden';
            DoctumSearch.doctumSearchPageAutoCompleteProgressBar.className = 'progress-bar';
        }
    },
    makeProgess: function () {
        Doctum.makeProgressOnProgressBar(
            Doctum.doctumSearchAutoCompleteProgressBarPercent,
            Doctum.doctumSearchAutoCompleteProgressBar
        );
        if (typeof DoctumSearch === 'object' && DoctumSearch.pageFullyLoaded) {
            Doctum.makeProgressOnProgressBar(
                Doctum.doctumSearchAutoCompleteProgressBarPercent,
                DoctumSearch.doctumSearchPageAutoCompleteProgressBar
            );
        }
    },
    loadAutoCompleteData: function (query) {
        return new Promise(function (resolve, reject) {
            if (Doctum.autoCompleteData !== null) {
                resolve(Doctum.autoCompleteData);
                return;
            }
            Doctum.markInProgress();
            function reqListener() {
                Doctum.autoCompleteLoading = false;
                Doctum.autoCompleteData = JSON.parse(this.responseText).items;
                Doctum.markProgressFinished();

                setTimeout(function () {
                    resolve(Doctum.autoCompleteData);
                }, 50);// Let the UI render once before sending the results for processing. This gives time to the progress bar to hide
            }
            function reqError(err) {
                Doctum.autoCompleteLoading = false;
                Doctum.autoCompleteData = null;
                console.error(err);
                reject(err);
            }

            var oReq = new XMLHttpRequest();
            oReq.onload = reqListener;
            oReq.onerror = reqError;
            oReq.onprogress = function (pe) {
                if (pe.lengthComputable) {
                    Doctum.doctumSearchAutoCompleteProgressBarPercent = parseInt(pe.loaded / pe.total * 100, 10);
                    Doctum.makeProgess();
                }
            };
            oReq.onloadend = function (_) {
                Doctum.markProgressFinished();
            };
            oReq.open('get', Doctum.autoCompleteDataUrl, true);
            oReq.send();
        });
    },
    /**
     * Make some progress on a progress bar
     *
     * @param number percentage
     * @param HTMLElement progressBar
     * @return void
     */
    makeProgressOnProgressBar: function(percentage, progressBar) {
        progressBar.className = 'progress-bar';
        progressBar.style.width = percentage + '%';
        progressBar.setAttribute(
            'aria-valuenow', percentage
        );
    },
    searchEngine: function (query, record) {
        if (typeof query !== 'string') {
            return '';
        }
        // replace all (mode = g) spaces and non breaking spaces (\s) by pipes
        // g = global mode to mark also the second word searched
        // i = case insensitive
        // how this function works:
        // First: search if the query has the keywords in sequence
        // Second: replace the keywords by a mark and leave all the text in between non marked
        
        if (record.match(new RegExp('(' + query.replace(/\s/g, ').*(') + ')', 'gi')) === null) {
            return '';// Does not match
        }

        var replacedRecord = record.replace(new RegExp('(' + query.replace(/\s/g, '|') + ')', 'gi'), function (group) {
            return '<mark class="auto-complete-highlight">' + group + '</mark>';
        });

        if (replacedRecord !== record) {
            return replacedRecord;// This should not happen but just in case there was no match done
        }

        return '';
    },
    /**
     * Clean the search query
     *
     * @param string query
     * @return string
     */
    cleanSearchQuery: function (query) {
        // replace any chars that could lead to injecting code in our regex
        // remove start or end spaces
        // replace backslashes by an escaped version, use case in search: \myRootFunction
        return query.replace(Doctum.querySearchSecurityRegex, '').trim().replace(/\\/g, '\\\\');
    },
    bootAutoComplete: function () {
        Doctum.autoCompleteJS = new autoComplete(
            {
                selector: '#doctum-search-auto-complete',
                searchEngine: function (query, record) {
                    return Doctum.searchEngine(query, record);
                },
                submit: true,
                data: {
                    src: function (q) {
                        Doctum.markInProgress();
                        return Doctum.loadAutoCompleteData(q);
                    },
                    keys: ['n'],// Data 'Object' key to be searched
                    cache: false, // Is not compatible with async fetch of data
                },
                query: (input) => {
                    return Doctum.cleanSearchQuery(input);
                },
                trigger: (query) => {
                    return Doctum.cleanSearchQuery(query).length > 0;
                },
                resultsList: {
                    tag: 'ul',
                    class: 'auto-complete-dropdown-menu',
                    destination: '#auto-complete-results',
                    position: 'afterbegin',
                    maxResults: 500,
                    noResults: false,
                },
                resultItem: {
                    tag: 'li',
                    class: 'auto-complete-result',
                    highlight: 'auto-complete-highlight',
                    selected: 'auto-complete-selected'
                },
            }
        );
    }
};


document.addEventListener('DOMContentLoaded', Doctum.pagePartiallyLoaded, false);
window.addEventListener('load', Doctum.pageFullyLoaded, false);

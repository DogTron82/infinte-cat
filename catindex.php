<?php
/*
Plugin Name: Category Expander
Description: Adds expandable categories to the sidebar with an "Alle produkter" link.
Version: 1.0
Author: Gustav Öman
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// JavaScript to add "+" sign to parent categories
function custom_plus() {
    ?>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            // Add "+" sign to parent categories
            document.querySelectorAll('.cat-parent > a').forEach(function (parentLink) {
                let plusSign = document.createElement('span');
                plusSign.classList.add('plus-sign');
                plusSign.textContent = ' +';
                parentLink.appendChild(plusSign);
            });

            // Smooth animation for non-parent categories
            document.querySelectorAll('.product-categories li:not(.cat-parent)').forEach(function (nonParent) {
                nonParent.style.transition = 'all 0.3s ease';
            });
        });
    </script>
    <?php
}
add_action('wp_footer', 'custom_plus');

// JavaScript to handle hide/expand functionality for unlimited levels of categories
function custom_category_js() {
    ?>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            // Function to toggle visibility and max-height of children
            function toggleChildrenVisibility(parentItem) {
                const childrenList = parentItem.querySelector('.children');
                if (parentItem.classList.contains('active')) {
                    childrenList.style.maxHeight = (getNestedHeight(childrenList) * 1.05) + 'px'; // Add 5% height
                    parentItem.querySelector('.plus-sign').textContent = ' -';
                } else {
                    childrenList.style.maxHeight = '0';
                    parentItem.querySelector('.plus-sign').textContent = ' +';
                }
            }

            // Recursive function to get total height of all nested children
            function getNestedHeight(element) {
                let totalHeight = 0;
                Array.from(element.children).forEach(function (child) {
                    totalHeight += child.scrollHeight;
                    if (child.classList.contains('cat-parent')) {
                        const nestedChildren = child.querySelector('.children');
                        if (nestedChildren) {
                            totalHeight += getNestedHeight(nestedChildren);
                        }
                    }
                });
                return totalHeight;
            }

            // Add "Alle produkter" link to the top of each child list
            function addAllProductsLink() {
                document.querySelectorAll('.cat-parent').forEach(function (parentItem) {
                    const childrenList = parentItem.querySelector('.children');
                    if (childrenList) {
                        const parentLink = parentItem.querySelector('a').href;
                        const allProductsItem = document.createElement('li');
                        const allProductsLink = document.createElement('a');
                        allProductsLink.href = parentLink;
                        allProductsLink.textContent = 'Alle produkter';
                        allProductsItem.appendChild(allProductsLink);
                        childrenList.insertBefore(allProductsItem, childrenList.firstChild);
                    }
                });
            }

            // Add event listener to all parent category links
            document.querySelectorAll('.cat-parent > a').forEach(function (parentLink) {
                parentLink.addEventListener('click', function (event) {
                    event.preventDefault();
                    const parentItem = this.parentElement;

                    // Toggle the active class
                    parentItem.classList.toggle('active');

                    // Adjust the visibility of the children list
                    toggleChildrenVisibility(parentItem);

                    // Close other active parents on the same level
                    const siblingParents = parentItem.parentElement.querySelectorAll('.cat-parent');
                    siblingParents.forEach(function (sibling) {
                        if (sibling !== parentItem && sibling.classList.contains('active')) {
                            sibling.classList.remove('active');
                            toggleChildrenVisibility(sibling);
                        }
                    });
                });
            });

            // Initialize the max-height for all children lists to 0
            document.querySelectorAll('.children').forEach(function (childrenList) {
                childrenList.style.maxHeight = '0';
            });

            // Keep expanded state on page load for categories marked as active
            document.querySelectorAll('.cat-parent.active > .children').forEach(function (childrenList) {
                childrenList.style.maxHeight = (getNestedHeight(childrenList) * 1.05) + 'px'; // Add 5% height
                childrenList.parentElement.querySelector('.plus-sign').textContent = ' -';

                // Ensure nested active children are also expanded
                const nestedParents = childrenList.querySelectorAll('.cat-parent.active > .children');
                nestedParents.forEach(function (nestedChildrenList) {
                    nestedChildrenList.style.maxHeight = (getNestedHeight(nestedChildrenList) * 1.05) + 'px'; // Add 5% height
                    nestedChildrenList.parentElement.querySelector('.plus-sign').textContent = ' -';
                });
            });

            // Add "Alle produkter" links after DOM is fully loaded
            addAllProductsLink();
        });

        document.addEventListener('DOMContentLoaded', function() {
            let isLoading = false;
            let nextPageUrl = document.querySelector('.next.page-numbers') ? document.querySelector('.next.page-numbers').href : null;

            // Function to show spinner
            function showSpinner() {
                // Check if spinner already exists to avoid duplicates
                if (!document.querySelector('#loading-spinner')) {
                    const spinner = document.createElement('div');
                    spinner.id = 'loading-spinner';
                    spinner.innerHTML = '<div class="spinner" style="border: 4px solid rgba(0,0,0,.1); width: 36px; height: 36px; border-radius: 50%; border-left-color: #09f; animation: spin 1s infinite linear;"></div>';
                    document.body.appendChild(spinner);

                    // Spinner style
                    spinner.style.position = 'fixed';
                    spinner.style.top = '50%';
                    spinner.style.left = '50%';
                    spinner.style.transform = 'translate(-50%, -50%)';
                    spinner.style.display = 'flex';
                    spinner.style.alignItems = 'center';
                    spinner.style.justifyContent = 'center';
                }
            }

            // Function to hide spinner
            function hideSpinner() {
                const spinner = document.querySelector('#loading-spinner');
                if (spinner) {
                    spinner.remove();
                }
            }

            window.addEventListener('scroll', function() {
                var scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
                var windowHeight = window.innerHeight;
                var totalPageHeight = document.body.offsetHeight;
                var triggerHeight = totalPageHeight * 0.7; // Calculate 70% of the total page height

                if (scrollPosition + windowHeight >= triggerHeight && nextPageUrl && !isLoading) {
                    isLoading = true;
                    showSpinner(); // Show spinner before fetching content
                    fetchNextPageContent();
                }
            });

            function fetchNextPageContent() {
                fetch(nextPageUrl)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const nextProducts = doc.querySelector('.products');

                        if (nextProducts) {
                            Array.from(nextProducts.children).forEach(child => {
                                document.querySelector('#primary .products').appendChild(child);
                            });
                        }

                        const nextPageLink = doc.querySelector('.next.page-numbers');
                        nextPageUrl = nextPageLink ? nextPageLink.href : null;

                        if (!nextPageLink) {
                            const currentNextLink = document.querySelector('.next.page-numbers');
                            if (currentNextLink) {
                                currentNextLink.remove();
                            }
                        }
                        hideSpinner(); // Hide spinner after content is loaded
                        isLoading = false;
                    })
                    .catch(error => {
                        console.error('Error loading next page content:', error);
                        hideSpinner(); // Ensure spinner is hidden on error
                        isLoading = false;
                    });
            }
        });

        document.addEventListener("DOMContentLoaded", function() {
            var filterGroups = document.querySelectorAll(".filter-group");

            filterGroups.forEach(function(group) {
                var selects = group.querySelectorAll("select");
                var labels = group.querySelectorAll("label");
                var elements = [...selects, ...labels];
                var container = document.createElement('div');

                container.style.display = 'flex';
                container.style.flexWrap = 'wrap';
                container.style.gap = '0px 5px';
                container.style.width = '100%';

                elements.forEach(function(el) {
                    el.style.flex = '1 2 calc(25% - 10px)';
                    el.style.marginBottom = '10px';
                    container.appendChild(el);
                });

                group.innerHTML = '';
                group.appendChild(container);
            });
        });
    </script>
    <?php
}
add_action('wp_footer', 'custom_category_js');
?>

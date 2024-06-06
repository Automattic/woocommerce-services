/**
 * External dependencies
 */
import React from 'react';
import { Flex, FlexItem, Modal, Icon } from '@wordpress/components';
import { useCallback, useState } from '@wordpress/element';

import bg from './images/wcshipping-migration.jpg';
import { Dashboard, Preformatted, LessonPlan, Shipping } from './icons';

const FeatureAnnouncement = () => {
	const [isOpen, setIsOpen] = useState(true);
	const closeModal = useCallback(() => {
		setIsOpen(false);
	});

	return <>{isOpen && (<Modal className="migration__announcement-modal"
															shouldCloseOnEsc={false}
															shouldCloseOnClickOutside={false}
															onRequestClose={closeModal}>
		<Flex gap={8}>
			<FlexItem>
				<Flex>
					<h2>
						A new dedicated WooCommerce Shipping extension is now available
					</h2>
					<p>WooCommerce Shipping and WooCommerce Tax are now two dedicated extensions. We'll automatically deactivate
						WooCommerce Shipping & Tax and carry over your settings when you update.</p>
					<p>Here's what you can expect from the new shipping experience:</p>

					<ul>
						<li>
							<Icon icon={Dashboard}/>
							<div>
								<h3>
									A seamless transition
								</h3>
								<p>
									All of your settings and shipment history have been imported to the new extension.
								</p>
							</div>
						</li>
						<li>
							<Icon icon={Preformatted}/>
							<div>
								<h3>
									Print and save
								</h3>
								<p>
									Speed up label creation with a streamlined process to print and save your label preferences.
								</p>
							</div>
						</li>
						<li>
							<Icon icon={Shipping}/>
							<div>
								<h3>
									USPS and DHL Express
								</h3>
								<p>
									Send using trusted shipping carriers like USPS and DHL Express, with more options and carriers coming
									soon.
								</p>
							</div>
						</li>

						<li>
							<Icon icon={LessonPlan}/>
							<div>
								<h3>
									Enhanced label purchase flow
								</h3>
								<p>
									Experience a smoother label purchasing process with our updated interface.
								</p>
							</div>
						</li>
					</ul>
				</Flex>
			</FlexItem>
			<FlexItem
				style={{
					background: `url(${bg}) no-repeat center center`, backgroundSize: 'contain'
				}}
			/>
		</Flex>
	</Modal>)}</>;

};

export default FeatureAnnouncement;

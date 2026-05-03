import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Edit() {
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<Placeholder
				label={ __( 'Show Article Map', 'show-article-map' ) }
				instructions={ __(
					'The article link map will be rendered on the front end.',
					'show-article-map'
				) }
			/>
		</div>
	);
}

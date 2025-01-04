import { createElement } from '@wordpress/element';

export interface FeatureMetadata {
  name: string;
  description: string;
}

export interface FeatureContext {
  dispatch: (scope: string) => (action: any) => void;
  apiUrl: string;
  nonce: string;
  debug?: boolean;
}

export interface FeatureRenderProps {
  userId: number;
}

export interface Feature {
  readonly identifier: string;
  readonly metadata: FeatureMetadata;
  register(context: FeatureContext): Promise<void>;
  init(): Promise<void>;
  isEnabled(): boolean;
  render(props: FeatureRenderProps): JSX.Element | null;
  cleanup(): Promise<void>;
  onNavigate?(): void;
  onUserChange?(userId: number): void;
} 
export type SpacerProps = {
    height: number;
};

export function SpacerBlock({ height }: SpacerProps) {
    return <div style={{ height: `${height}px` }} aria-hidden="true" />;
}

export const spacerFields = {
    height: { type: 'number' as const, label: 'Tinggi (px)', min: 0, max: 200 },
};

export const spacerDefaultProps: SpacerProps = {
    height: 32,
};
